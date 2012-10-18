<?php

namespace APubSub\Drupal7;

use APubSub\Impl\DefaultMessage;
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
     * @var \APubSub\Drupal7\D7PubSub
     */
    private $backend;

    /**
     * Default constructor
     *
     * @param D7PubSub $backend Backend that owns this instance
     * @param scalar $id        User set identifier
     */
    public function __construct(D7PubSub $backend, $id)
    {
        $this->id = $id;
        $this->backend = $backend;

        $this->setContext($this->backend->getContext());
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
        // FIXME: Needs static caching
        $idList = $this
            ->context
            ->dbConnection
            ->query("SELECT sub_id FROM {apb_sub_map} WHERE id = :id", array(
                ':id' => $this->id,
            ))
            ->fetchCol();

       return $this->backend->getSubscriptions($idList);
    }

    /**
     * Get the subscription for a specific channel if exists
     *
     * @param string $channelId               Channel identifier
     *
     * @return \APubSub\SubscriptionInterface Subscription instance
     *
     * @throws \APubSub\Error\SubscriptionDoesNotExistException
     *                                        If the subscriber did not
     *                                        subscribe to the given channel
     * @throws \APubSub\Error\ChannelDoesNotExistException
     *                                        If channel does not exist
     */
    public function getSubscriptionFor($channelId)
    {
        throw new \Exception("Not implemented yet");
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
        $channel   = $this->backend->getChannel($channelId);
        $created   = $activated;
        $cx        = $this->context->dbConnection;
        $tx        = $cx->startTransaction();

        // FIXME: Must ensure the subscription does not already exist

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

            return new D7SimpleSubscription($channel,
                $id, $created, $activated, 0, false);

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
    protected function getMessages($idList, $limit = null, $reverse = false)
    {
        throw new \Exception("Not implemented yet");
        $ret = array();

        if (empty($idList)) {
            return array();
        }

        $query = $this
            ->context
            ->dbConnection
            ->select('apb_msg', 'm')
            ->fields('m')
            ->condition('m.id', $idList, 'IN');

        $query
            ->join();

        if (null !== $limit) {
            //
        }

        if ($reverse) {
            //
        }

        $results = $query
            ->execute();

        // Populate messages array
        foreach ($results as $record) {
            $ret[] = new DefaultMessage($this->backend,
                (string)$record->chan_id, unserialize($record->contents),
                (int)$record->id, (int)$record->created);
        }

        $this
            ->context
            ->dbConnection
            ->delete('apb_queue')
            ->condition('msg_id', $idList, 'IN')
            ->execute();

        return $ret;
    }

    /**
     * Fetch oldest messages in queue all active subscriptions included
     *
     * @param int $limit Number of messages to fetch
     *
     * @return array     List of MessageInterface instances ordered by ascending
     *                   creation timestamp
     */
    public function fetchHead($limit)
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Fetch latest messages in queue all active subscriptions included
     *
     * @param int $limit Number of messages to fetch
     *
     * @return array     List of MessageInterface instances ordered by ascending
     *                   creation timestamp
     */
    public function fetchTail($limit)
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Fetch all messages in queue all active subscriptions included
     *
     * This method will trigger 3 SQL queries
     *
     * @return array List of MessageInterface instances ordered by ascending
     *               creation timestamp
     */
    public function fetch()
    {
      /*
        // And the winner is:
      SELECT m.id, m.* FROM apb_sub_map mp
      JOIN apb_queue q ON q.sub_id = mp.sub_id
      JOIN apb_msg m ON m.id = q.msg_id
      WHERE mp.name = 'user:9991'
          ORDER BY m.id ASC;

           FIXME: Add standalone index on apb_queue.sub_id

           */
        $idList = $this
            ->context
            ->dbConnection
            ->query("
                SELECT msg_id FROM {apb_queue}
                    WHERE sub_id IN (
                        SELECT sub_id FROM {apb_sub_map}
                            WHERE name = :name
                    )
                    ORDER BY msg_id ASC
            ", array(
                ':name' => $this->id,
            ));

        return $this->getMessages($idList);
    }
}
