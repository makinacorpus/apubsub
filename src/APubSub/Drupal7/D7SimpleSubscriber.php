<?php

namespace APubSub\Drupal7;

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
     * Create a new subscription for a specific channel
     *
     * Note that unlike the subscription, subscribing from here must activate
     * the subscription right away
     *
     * @param string $channelId               Channel identifier
     *
     * @return \APubSub\SubscriptionInterface New subscription instance
     *
     * @throws \APubSub\Error\SubscriptionDoesNotExistException
     *                                        If the subscriber already have
     *                                        been subscribed to this channel
     * @throws \APubSub\Error\ChannelDoesNotExistException
     *                                        If channel does not exist
     */
    public function subscribe($channelId)
    {
        throw new \Exception("Not implemented yet");
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
     * @return array List of MessageInterface instances ordered by ascending
     *               creation timestamp
     */
    public function fetch()
    {
        throw new \Exception("Not implemented yet");
    }
}
