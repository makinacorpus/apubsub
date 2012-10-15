<?php

namespace APubSub\Drupal7;

use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\PubSubInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class D7PubSub extends AbstractD7Object implements PubSubInterface
{
    /**
     * Default constructor
     *
     * @param DatabaseConnection $dbConnection Drupal database connexion
     * @param array $options                   Options
     */
    public function __construct(\DatabaseConnection $dbConnection, array $options = null)
    {
        $this->setContext(new D7Context($dbConnection, $options));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannel()
     */
    public function getChannel($id)
    {
        // FIXME: We could use a static cache here, but the channel might have
        // been deleted in another thread, however, this is very unlikely to
        // happen
        $record = $this
            ->context
            ->dbConnection
            ->query("SELECT * FROM {apb_chan} WHERE name = :name", array(':name' => $id))
            ->fetchObject();

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        return new D7SimpleChannel($this, $record->name, (int)$record->id, (int)$record->created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannel()
     */
    public function getChannelByDatabaseId($id)
    {
        // FIXME: We could use a static cache here, but the channel might have
        // been deleted in another thread, however, this is very unlikely to
        // happen
        $record = $this
            ->context
            ->dbConnection
            ->query("SELECT * FROM {apb_chan} WHERE id = :id", array(':id' => $id))
            ->execute()
            ->fetchObject();

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        return new D7SimpleChannel($this, $record->name, (int)$record->id, (int)$record->created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannel()
     */
    public function createChannel($id)
    {
        $channel = null;
        $created = time();
        $dbId    = null;
        $cx      = $this->context->dbConnection;
        $tx      = $cx->startTransaction();

        try {
            // No not ever use cache here, we cannot afford to try to create
            // a channel that have been deleted by another thread
            $exists = $cx
                ->query("SELECT 1 FROM {apb_chan} c WHERE c.name = :name", array(
                    ':name' => $id,
                ))
                ->fetchField();

            if ($exists) {
                throw new ChannelAlreadyExistsException();
            }

            $cx
                ->insert('apb_chan')
                ->fields(array(
                    'name' => $id,
                    'created' => $created,
                ))
                ->execute();

            $dbId = (int)$cx->lastInsertId();

            unset($tx); // Explicit commit

            $channel = new D7SimpleChannel($this, $id, $dbId, $created);

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return $channel;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        $dbId = null;
        $cx   = $this->context->dbConnection;
        $tx   = $cx->startTransaction();

        try {
            // FIXME: SELECT FOR UPDATE here in all tables

            $dbId = (int)$cx
                ->query("SELECT id FROM {apb_chan} WHERE name = :name", array(
                    ':name' => $id,
                ))
                ->fecthField();

            if (!$dbId) {
                throw new ChannelDoesNotExistException();
            }

            $args = array(':dbId' => $dbId);

            // Queue is not the most optimized query, but it is necessary: this
            // means that consumers will lost unseen messages
            // FIXME: Joining with apb_sub instead might be more efficient
            $cx->query("DELETE FROM {apb_queue} q WHERE q.msg IN (SELECT m.id FROM {apb_msg} m WHERE m.chan_id = :dbId)", $args);

            // Delete subscriptions and messages
            $cx->query("DELETE FROM {apb_msg} WHERE chan_id = :dbId", $args);
            $cx->query("DELETE FROM {apb_sub} WHERE chan_id = :dbId", $args);

            // Finally the last strike
            $cx->query("DELETE FROM {apb_chan} WHERE id = :dbId", $args);

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscription()
     */
    public function getSubscription($id)
    {
        // FIXME: We could use a static cache here, but the channel might have
        // been deleted in another thread, however, this is very unlikely to
        // happen
        $record = $this
            ->context
            ->dbConnection
            ->query("SELECT * FROM {apb_sub} WHERE id = :id", array(
                ':id' => $id,
            ))
            ->fetchObject();

        if (!$record || !($channel = $this->getChannelByDatabaseId($record->chan_id))) {
            // Subscription may exist, but channel does not anymore case in
            // which we consider it should be dropped
            throw new SubscriptionDoesNotExistException();
        }

        return new D7SimpleSubscription($channel, (int)$record->id,
            (int)$record->created, (int)$record->activated,
            (int)$record->deactivated, (bool)$record->status);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscription()
     */
    public function deleteSubscription($id)
    {
        $cx = $this->context->dbConnection;
        $tx = $cx->startTransaction();

        try {
            $args = array(':id' => $dbId);

            // FIXME: SELECT FOR UPDATE here in all tables

            $exists = (bool)$cx
                ->query("SELECT 1 FROM {apb_sub} WHERE id = :id", $args)
                ->fecthField();

            if (!$exists) {
                // See comment in createChannel() method
                $tx->rollback();

                throw new ChannelDoesNotExistException();
            }

            // Clean queue then delete subscription
            $cx->query("DELETE FROM {apb_queue} WHERE sub_id = :id", $args);
            $cx->query("DELETE FROM {apb_sub} WHERE id = :id", $args);

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscriptions()
     */
    public function deleteSubscriptions($idList)
    {
        // FIXME: Optimize this if necessary
        foreach ($idList as $id) {
            $this->deleteSubscription($id);
        }
    }
}
