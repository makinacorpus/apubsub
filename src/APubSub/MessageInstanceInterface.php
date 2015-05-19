<?php

namespace APubSub;

/**
 * A message instance is what lives into the queue: it is tied to one or more
 * channels and to one or more subscriptions, and has a state
 */
interface MessageInstanceInterface extends MessageInterface
{
    /**
     * Get internal queue message identifier
     *
     * @return scalar Identifier whose type depends on the backend
     *                implementation, and which makes the message
     *                unique into the global message queue
     */
    public function getQueueId();

    /**
     * Is this message unread
     *
     * @return bool
     */
    public function isUnread();

    /**
     * Set unread status for the current subscription
     *
     * @param bool $toggle New read status false for read, true for unread
     */
    public function setUnread($toggle = true);

    /**
     * Get send date
     *
     * @return \DateTime
     */
    public function getSendDate();

    /**
     * Get read date
     *
     * @return \DateTime
     */
    public function getReadDate();

    /**
     * Get subscription identifier
     *
     * Use this method rather than getSubscription() whenever possible, in
     * order to avoid backends lookup in most implementations
     *
     * @return mixed
     *   Subscription identifier
     */
    public function getSubscriptionId();

    /**
     * Get channel
     *
     * @return \APubSub\SubscriptionInterface
     */
    public function getSubscription();
}
