<?php

namespace MakinaCorpus\APubSub;

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
interface SubscriberInterface extends MessageContainerInterface
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
     * @return SubscriptionInterface[]
     *   List of SubscriptionInterface instances
     */
    public function getSubscriptions();

    /**
     * Get subscriptions identifiers
     *
     * @return scalar[] 
     */
    public function getSubscriptionsIds();

    /**
     * Has the current subscriber a subscription for the given channel.
     *
     * @param string $chanId
     *   Channel identifier
     */
    public function hasSubscriptionFor($chanId);

    /**
     * Get the subscription for a specific channel if exists
     *
     * @param string $chanId
     *   Channel identifier
     *
     * @return \MakinaCorpus\APubSub\SubscriptionInterface
     *   Subscription instance
     *
     * @throws \MakinaCorpus\APubSub\Error\SubscriptionDoesNotExistException
     *   If the subscriber did not subscribe to the given channel
     */
    public function getSubscriptionFor($chanId);

    /**
     * Create a new subscription for a specific channel
     *
     * Note that unlike the subscription, subscribing from here must activate
     * the subscription right away
     *
     * If subscription already exists, be silent about about it
     *
     * @param string $chanId
     *   Channel identifier
     *
     * @return \MakinaCorpus\APubSub\SubscriptionInterface
     *   New subscription instance
     *
     * @throws \MakinaCorpus\APubSub\Error\ChannelDoesNotExistException
     *   If channel does not exist
     */
    public function subscribe($chanId);

    /**
     * Create a new subscription for a specific channel
     *
     * Note that unlike the subscription, subscribing from here must activate
     * the subscription right away
     *
     * If subscription nor chan do not exist, be silent about it. 
     *
     * @param string $chanId
     *   Channel identifier
     */
    public function unsubscribe($chanId);
}
