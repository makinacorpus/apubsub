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
interface SubscriberInterface extends
    ObjectInterface,
    MessageContainerInterface
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
     * If subscription already exists, be silent about about it
     *
     * @param string $channelId               Channel identifier
     *
     * @return \APubSub\SubscriptionInterface New subscription instance
     *
     * @throws \APubSub\Error\ChannelDoesNotExistException
     *                                        If channel does not exist
     */
    public function subscribe($channelId);

    /**
     * Create a new subscription for a specific channel
     *
     * Note that unlike the subscription, subscribing from here must activate
     * the subscription right away
     *
     * If subscription nor chan do not exist, be silent about it. 
     *
     * @param string $channelId Channel identifier
     */
    public function unsubscribe($channelId);

    /**
     * Get latest access time
     *
     * This does not change the messaging system behavior but is meant to be
     * used for processing cron, in order to be able to filter and order
     * subscribers using this
     *
     * @return int Unix timestamp
     */
    public function getLastAccessTime();

    /**
     * Set this subscriber latest access time to now
     */
    public function touch();

    /**
     * Fetch current message queue
     *
     * @param array $conditions  Array of key value pairs conditions, only the
     *                           "equal" operation is supported. If value is an
     *                           array, treat it as a "IN" operator
     *
     * @return CursorInterface   Iterable object of messages
     */
    public function fetch(array $conditions = null);

    /**
     * Delete everyting in all of this subscriber's subscription queues
     */
    public function flush();

    /**
     * Delete all subscriptions related to this subscriber
     */
    public function delete();

    /**
     * Get extra data
     *
     * @return array Extra data
     */
    public function getExtraData();

    /**
     * Set extra data
     *
     * @param array $data Extra data
     */
    public function setExtraData(array $data);
}
