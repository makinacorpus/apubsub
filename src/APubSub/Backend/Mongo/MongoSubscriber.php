<?php

namespace APubSub\Backend\Mongo;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\DefaultMessage;
use APubSub\Backend\Mongo\Cursor\MongoMessageCursor;
use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\CursorInterface;
use APubSub\SubscriberInterface;

class MongoSubscriber extends AbstractObject implements SubscriberInterface
{
    /**
     * Identifier
     *
     * @var string
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
     * @param MongoContext $context Backend that owns this instance
     * @param string $id            User set identifier
     */
    public function __construct(MongoContext $context, $id)
    {
        $this->id      = $id;
        $this->context = $context;
        $this->idList  = array();

        $records = $this
            ->context
            ->subCollection
            ->find(
                array(
                    'subscriber' => $this->id,
                ),
                array(
                    '_id'       => true,
                    'chan_name' => true,
                )
            );

        foreach ($records as $record) {
            $this->idList[$record['chan_name']] = (string)$record['_id'];
        }
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
        throw new \Exception("Not implemented yet");

        /*
        $activated = time();
        $created   = $activated;
        $cx        = $this->context->dbConnection;
        $tx        = $cx->startTransaction();

        if (isset($this->idList[$channelId])) {
            throw new SubscriptionAlreadyExistsException();
        }

        // Load the channel only once the subscription load has been attempted,
        // this ensures a few static caches may have been built ahead of us
        $channel = $this->context->backend->getChannel($channelId);

        try {
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

            $subscription = new MongoSubscription(
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
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetch()
     */
    public function fetch(array $conditions = null)
    {
        throw new \Exception("Not implemented yet");

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
/*
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

        // FIXME: Apply conditions.

        return new MongoMessageCursor($this->context, $query);
 */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::flush()
     */
    public function flush()
    {
        $subIdList = array();
        foreach ($this->idList as $id) {
            $subIdList[] = new \MongoId($id);
        }

        $this
            ->context
            ->queueCollection
            ->remove(array(
                'sub_id' => array(
                     '$in' => $subIdList,
                ),
            ));
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
        $subIdList = array();
        foreach ($this->idList as $id) {
            $subIdList[] = new \MongoId($id);
        }

        foreach ($idList as $key => $id) {
            $idList[$key] = new \MongoId($id);
        }

        $this
            ->context
            ->queueCollection
            ->remove(array(
                'sub_id' => array(
                     '$in' => $subIdList,
                ),
                'msg_id' => array(
                    '$in' => $idList,
                ),
            ));
    }
}
