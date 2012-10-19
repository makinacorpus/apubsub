<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultMessage;
use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\SubscriberInterface;

/**
 * Drupal 7 simple subscriber implementation
 */
class D7SimpleSubscriber extends AbstractD7Object implements SubscriberInterface
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
            // from the subscriber map
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
        $activated = time();
        // Should be moved out and a subselect used instead, this triggers an
        // extra and useless SQL query
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

            $subscription = new D7SimpleSubscription(
                $this->context, $channel->getDatabaseId(),
                $id, $created, $activated, 0, false);

            // Also ensure a few static caches are setup in the global context:
            // this will be the only cache instance direct access from this
            // class
            $this->context->cache->addSubscription($subscription);
            $this->idList[$channelId] = $id;

            return $subscription;

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * Get messages independently from channels
     *
     * This is an internal helper method: per design loading a message without
     * a channel is not allowed. This method is error tolerant and will not fail
     * if some messsages have been dropped
     *
     * This method will do 2 SQL queries, one for fetch messages, and the other
     * one for removing those messages from the list.
     *
     * @param array $idList List of message identifiers
     * @param int $limit    Number of message to fetch
     * @param bool $reverse Set this to true if you want to fetch latest
     *                      messages instead of oldest messages
     *
     * @return array        List of DefaultMessage instances
     */
    protected function getMessages($limit = null, $reverse = false)
    {
        $ret    = array();
        $idList = array();

        /*
         * Targeted query: benchmarked along 4 different variants, including
         * subqueries, different JOIN order, different indexes: this is one
         * is the one that will give you the best performances with MySQL.
         *
         * SELECT m.id, m.* FROM apb_sub_map mp
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
         * the apb_queue table.
         *
         * Note that for other DBMS' this will need to be tested, and a
         * switch/case on the dbConnection class may proove itself to be very
         * efficient if needed.
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
            ->condition('mp.name', $this->id);

        if (null !== $limit) {
            $query->range(0, $limit);
        }
        if ($reverse) {
            $query->orderBy('m.id', 'DESC');
        } else {
            $query->orderBy('m.id', 'ASC');
        }

        // Applause.
        $results = $query->execute();

        foreach ($results as $record) {
            $ret[] = new DefaultMessage($this->context,
                (string)$record->chan_id, unserialize($record->contents),
                (int)$record->id, (int)$record->created);

            $idList[] = (int)$record->id;
        }

        // Queue removal: very important step
        $this
            ->context
            ->dbConnection
            ->delete('apb_queue')
            ->condition('msg_id', $idList, 'IN')
            ->execute();

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetchHead()
     */
    public function fetchHead($limit)
    {
        return $this->getMessages($limit);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetchTail()
     */
    public function fetchTail($limit)
    {
        return $this->getMessages($limit, true);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetch()
     */
    public function fetch()
    {
        return $this->getMessages();
    }
}
