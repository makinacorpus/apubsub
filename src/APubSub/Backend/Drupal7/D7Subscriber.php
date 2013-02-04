<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\Drupal7\Cursor\D7MessageCursor;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\CursorInterface;
use APubSub\SubscriberInterface;

/**
 * Drupal 7 simple subscriber implementation
 */
class D7Subscriber extends AbstractObject implements SubscriberInterface
{
    /**
     * Identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Subscription identifiers
     *
     * @var array
     */
    private $idList = null;

    /**
     * Default constructor
     *
     * @param D7Context $context Backend that owns this instance
     * @param scalar $id         User set identifier
     */
    public function __construct(D7Context $context, $id)
    {
        $this->id = $id;
        $this->context = $context;

        // Get subscription identifiers list, with channel mapping
        $this->idList = $this
            ->context
            ->dbConnection
            // This query will also remove non existing stalling subscriptions
            // from the subscriber map thanks to the JOIN statements, thus
            // avoiding potential exceptions being thrown at single subscription
            // get time
            ->query("
                SELECT c.name, mp.sub_id
                    FROM {apb_sub_map} mp
                    JOIN {apb_sub} s ON s.id = mp.sub_id
                    JOIN {apb_chan} c ON c.id = s.chan_id
                    WHERE mp.name = :name", array(
                ':name' => $this->id,
            ))
            ->fetchAllKeyed();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptions()
     */
    public function getSubscriptions()
    {
       return $this->context->backend->getSubscriptions($this->idList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptionFor()
     */
    public function getSubscriptionFor($channelId)
    {
        if (!isset($this->idList[$channelId])) {
            throw new SubscriptionDoesNotExistException();
        }

        // If another piece of code effectively deleted the subscription, but
        // we still work on an outdated cache, this should throw the same
        // exception as upper. Don't think this cannot happen, this *will*
        // happen, nothing prevent you from deleting a subscription using the
        // backend after you loaded this subscriber instance
        return $this->context->backend->getSubscription($this->idList[$channelId]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::subscribe()
     */
    public function subscribe($channelId)
    {
        try {
            if (isset($this->idList[$channelId])) {
                // See the getSubscriptionFor() implementation
                return $this->context->backend->getSubscription($this->idList[$channelId]);
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // Someone else deleted this subscription and we have cached it in
            // a wrong state, leave this method run until this end which will
            // recreate the subscription
        }

        $activated = time();
        $created   = $activated;
        $cx        = $this->context->dbConnection;
        $tx        = $cx->startTransaction();

        try {
            // Load the channel only once the subscription load has been attempted,
            // this ensures a few static caches may have been built ahead of us
            $channel = $this->context->backend->getChannel($channelId);

            $cx
                ->insert('apb_sub')
                ->fields(array(
                    'chan_id' => $channel->getDatabaseId(),
                    'status' => 1,
                    'created' => $created,
                    'activated' => $activated,
                ))
                ->execute();

            $id = (int)$cx->lastInsertId();

            $cx
                ->insert('apb_sub_map')
                ->fields(array(
                    'name' => $this->id,
                    'sub_id' => $id,
                ))
                ->execute();

            unset($tx); // Explicit commit

            $subscription = new D7Subscription(
                $this->context, $channel->getDatabaseId(),
                $id, $created, $activated, 0, false);

            // Also ensure a few static caches are setup in the global context:
            // this will be the only cache object instance direct access from
            // this class
            $this->context->cache->addSubscription($subscription);
            $this->idList[$channelId] = $id;

            return $subscription;

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::unsubscribe()
     */
    public function unsubscribe($channelId)
    {
        try {
            if (isset($this->idList[$channelId])) {
                // See the getSubscriptionFor() implementation
                $this->context->backend->getSubscription($this->idList[$channelId])->delete();
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // All OK
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetch()
     */
    public function fetch(array $conditions = null)
    {
        /*
         * Targeted query: benchmarked along 4 different variants, including
         * subqueries, different JOIN order, different indexes: this one
         * is the one that will give you the best performances with MySQL.
         *
         * SELECT q.*, m.* FROM apb_sub_map mp
         *     JOIN apb_queue q ON q.sub_id = mp.sub_id
         *     JOIN apb_msg m ON m.id = q.msg_id
         *     WHERE mp.name = 'user:9991'
         *     ORDER BY m.id ASC;
         *
         * MySQL EXPLAIN was specific enough in all variants to say without
         * any doubt this is the best one, fully using indexes, starting with
         * a CONST index, and using only ref and eq_ref JOIN types on known
         * INT32 indexes.
         *
         * On a poor box, with few CPU and few RAM this query runs in 0.01s
         * (MySQL result) with no query cache and 5 millions of records in
         * the apb_queue table and 300,000 in the apb_sub_map table.
         *
         * Note that for other DBMS' this will need to be tested, and a
         * switch/case on the dbConnection class may proove itself to be very
         * efficient if needed.
         *
         * Additionally, we need to apply some conditions over this query:
         *
         *     WHERE
         *       [CONDITIONS]
         *     ORDER BY [FIELD] [DIRECTION];
         *
         * Hopping those won't kill our queries.
         */

        $query = $this
            ->context
            ->dbConnection
            ->select('apb_sub_map', 'mp');
        $query
            ->join('apb_queue', 'q', 'q.sub_id = mp.sub_id');
        $query
            ->join('apb_msg', 'm', 'm.id = q.msg_id');
        $query
            ->fields('m')
            ->fields('q')
            ->condition('mp.name', $this->id);

        if (null !== $conditions) {
            foreach ($conditions as $field => $value) {
                switch ($field) {

                    case CursorInterface::FIELD_MSG_ID:
                        $query->condition('q.msg_id', $value);
                        break;

                    case CursorInterface::FIELD_MSG_UNREAD:
                        $query->condition('q.unread', $value);
                        break;

                    default:
                        trigger_error(sprintf("% does not support filter %d yet",
                            get_class($this), $field));
                        break;
                }
            }
        }

        return new D7MessageCursor($this->context, $query);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::flush()
     */
    public function flush()
    {
        $cx
            ->delete('apb_queue')
            ->condition('sub_id', $this->idList, 'IN')
            ->execute();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessage()
     */
    public function deleteMessage($id)
    {
        $this->deleteMessages(array($id));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessages()
     */
    public function deleteMessages(array $idList)
    {
        $this
            ->context
            ->dbConnection
            ->delete('apb_queue')
            ->condition('sub_id', $this->idList)
            ->condition('msg_id', $idList, 'IN')
            ->execute();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessage()
     */
    public function getMessage($id)
    {
        $cursor = $this->fetch(array(
            CursorInterface::FIELD_MSG_ID => $id,
        ));

        if (!count($cursor)) {
            throw new MessageDoesNotExistException();
        }

        foreach ($cursor as $message) {
            return $message;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessages(array $idList)
    {
        $cursor = $this->fetch(array(
            CursorInterface::FIELD_MSG_ID => $idList,
        ));

        if (count($cursor) !== count($idList)) {
            throw new MessageDoesNotExistException();
        }

        return iterator_to_array($cursor);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::delete()
     */
    public function delete()
    {
        $this->context->getBackend()->deleteSubscriptions($this->idList);
    }
}
