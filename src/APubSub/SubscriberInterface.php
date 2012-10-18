<?php

namespace APubSub;

use APubSub\Impl\ObjectInterface;

/**
 * A subscriber is an optional component of the API that may reference
 * one or more subscriptions
 *
 * This implementation provides numerous helpers for fetching messages that
 * would allow you to avoid using the subscription object directly
 *
 * Subscriber identifiers are given by the business layer using this API and
 * will be ensured to be unique when creating it
 */
interface SubscriberInterface extends ObjectInterface
{
    /**
     * Get identifier
     *
     * @return scalar User set identifier
     */
    public function getId();

    /**
     * Get all subscriptions
     *
     * @return array List of SubscriptionInterface instances
     */
    public function getSubscriptions();

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
    public function getSubscriptionFor($channelId);

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
    public function subscribe($channelId);

    /**
     * Fetch oldest messages in queue all active subscriptions included
     *
     * @param int $limit Number of messages to fetch
     *
     * @return array     List of MessageInterface instances ordered by ascending
     *                   creation timestamp
     */
    public function fetchHead($limit);

    /**
     * Fetch latest messages in queue all active subscriptions included
     *
     * @param int $limit Number of messages to fetch
     *
     * @return array     List of MessageInterface instances ordered by ascending
     *                   creation timestamp
     */
    public function fetchTail($limit);

    /**
     * Fetch all messages in queue all active subscriptions included
     *
     * @return array List of MessageInterface instances ordered by ascending
     *               creation timestamp
     */
    public function fetch();
}
