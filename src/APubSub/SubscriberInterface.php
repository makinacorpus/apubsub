<?php

namespace APubSub;

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
     * Fetch current message queue
     *
     * @param number $limit      Message count to fetch
     * @param number $offset     Start offset
     * @param array $conditions  Array of key value pairs conditions, only the
     *                           = operator is supported right now
     * @param string $sortField  Sort field: all CursorInterface::FIELD_*
     *                           constants will be supported by all backends
     * @param int $sortDirection Sort direction
     *
     * @return array             Array of messages
     */
    public function fetch(
        $limit            = CursorInterface::LIMIT_NONE,
        $offset           = 0,
        array $conditions = null,
        $sortField        = CursorInterface::FIELD_MSG_SENT,
        $sortDirection    = CursorInterface::SORT_DESC);

    /**
     * Delete everyting in all of this subscriber's subscription queues
     */
    public function flush();
}
