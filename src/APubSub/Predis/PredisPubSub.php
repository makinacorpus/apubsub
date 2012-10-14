<?php

namespace APubSub\Predis;

use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\PubSubInterface;

use Predis\Client;

/**
 * Predis based implementation.
 *
 *  - Each channel is basic key, whose name is prefixed.
 *
 *  - Each subscription is two things: a HSET that will contain all its
 *    properties and a sorted list of messages ids (queue)
 *
 *  - Each message is an HSET which will contain both the created and contents
 *    keys. This allows both sharding and primitive typed values to be stored
 *    efficiently.
 *
 * @todo
 *   - Moved helper functions into an helper object
 *   - Inject all of client, backend and helper into channel and subscription 
 */
class PredisPubSub implements PubSubInterface
{
    /**
     * Channel based key prefix
     */
    const KEY_PREFIX_CHAN = 'c:';

    /**
     * Subscription based key prefix
     */
    const KEY_PREFIX_MSG = 'm:';

    /**
     * Subscription based key prefix
     */
    const KEY_PREFIX_SUB = 's:';

    /**
     * Sequences key prefix
     */
    const KEY_PREFIX_SEQ = 'seq:';

    /**
     * @var \Predis\Client
     */
    protected $predisClient;

    /**
     * @var string
     */
    protected $keyPrefix = 'apb:';

    /**
     * Default constructor
     *
     * @param DatabaseConnection $dbConnection Drupal database connexion
     * @param string $keyPrefix                Key name prefix
     */
    public function __construct(Client $predisClient, $keyPrefix = null)
    {
        $this->predisClient = $predisClient;

        if (null !== $keyPrefix) {
            $this->keyPrefix = $keyPrefix;
        }
    }

    /**
     * Get key name
     *
     * @param string $name Original key name
     *
     * @return string      Prefixed key name
     */
    public function getKeyName($name)
    {
        return $this->keyPrefix . $name;
    }

    /**
     * Get next sequence id
     *
     * @param string $name
     */
    public function getNextId($name)
    {
        $seqKey  = $this->getKeyName(self::KEY_PREFIX_SEQ . $name);
        $retries = 5;

        do {
            $this->predisClient->watch($seqKey);
            $this->predisClient->multi();
            $value = $this->predisClient->incr($seqKey);
            $replies = $this->predisClient->exec();

        } while (!$replies[0] && 0 < $retries--);

        return $seqKey;
    }

    /**
     * Get Predis client
     *
     * @return \Predis\Client The predis client
     */
    public function getPredisClient()
    {
        return $this->predisClient;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannel()
     */
    public function getChannel($id)
    {
        $chanKey = $this->getKeyName(self::KEY_PREFIX_CHAN . 'id');

        if (!$created = $this->predisClient->get($chanKey)) {
            throw new ChannelDoesNotExistException();
        }

        return new PredisChannel($this, $id, (int)$created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannel()
     */
    public function getChannelByDatabaseId($id)
    {
        throw new \Exception("Not implemented yet");
        // FIXME: We could use a static cache here, but the channel might have
        // been deleted in another thread, however, this is very unlikely to
        // happen
        $record = $this
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
        $chanKey = $this->getKeyName(self::KEY_PREFIX_CHAN . 'id');
        $created = time();

        $this->predisClient->watch($chanKey);

        if ($this->predisClient->get($chanKey)) {
            throw new \ChannelAlreadyExistsException();
        }

        // FIXME: Later use pipelining.
        $this->predisClient->multi();
        $this->predisClient->set($chanKey, $created);
        $replies = $this->predisClient->exec();

        if (!$replies[0]) {
            // Transaction failed, another thread created the same channel
            throw new \ChannelAlreadyExistsException();
        }

        return new PredisChannel($this, $id, $created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        throw new \Exception("Not implemented yet");
        $dbId = null;
        $tx   = $this->dbConnection->startTransaction();

        try {
            // FIXME: SELECT FOR UPDATE here in all tables

            $dbId = (int)$this
                ->dbConnection
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
            $this->dbConnection->query("DELETE FROM {apb_queue} q WHERE q.msg IN (SELECT m.id FROM {apb_msg} m WHERE m.chan_id = :dbId)", $args);

            // Delete subscriptions and messages
            $this->dbConnection->query("DELETE FROM {apb_msg} WHERE chan_id = :dbId", $args);
            $this->dbConnection->query("DELETE FROM {apb_sub} WHERE chan_id = :dbId", $args);

            // Finally the last strike
            $this->dbConnection->query("DELETE FROM {apb_chan} WHERE id = :dbId", $args);

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
        throw new \Exception("Not implemented yet");
        // FIXME: We could use a static cache here, but the channel might have
        // been deleted in another thread, however, this is very unlikely to
        // happen
        $record = $this->dbConnection->query("SELECT * FROM {apb_sub} WHERE id = :id", array(':id' => $id))->fetchNext();

        if (!$record || !($channel = $this->getChannelByDatabaseId($record->chan_id))) {
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
        throw new \Exception("Not implemented yet");
        $tx = $this->dbConnection->startTransaction();

        try {
            $args = array(':id' => $dbId);

            // FIXME: SELECT FOR UPDATE here in all tables

            $exists = (bool)$this
                ->dbConnection
                ->query("SELECT 1 FROM {apb_sub} WHERE id = :id", $args)
                ->fecthField();

            if (!$exists) {
                // See comment in createChannel() method
                $tx->rollback();

                throw new ChannelDoesNotExistException();
            }

            // Clean queue then delete subscription
            $this->dbConnection->query("DELETE FROM {apb_queue} WHERE sub_id = :id", $args);
            $this->dbConnection->query("DELETE FROM {apb_sub} WHERE id = :id", $args);

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
        throw new \Exception("Not implemented yet");
        // FIXME: Optimize this if necessary
        foreach ($idList as $id) {
            $this->deleteSubscription($id);
        }
    }
}
