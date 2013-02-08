<?php

namespace APubSub;

use APubSub\ObjectInterface;

/**
 * Represent a specific message tied to a subscription
 *
 * In some rare case, you can fetch the message outside of the subscription
 * context, case where you should not attempt to get the subscription or you
 * would receive nasty exception.
 */
interface MessageInterface extends ObjectInterface
{
    /**
     * Get internal message identifier
     *
     * @return scalar Identifier whose type depends on the backend
     *                implementation
     */
    public function getId();

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
     * Get send UNIX timestamp
     *
     * @return int Send time UNIX timestamp
     */
    public function getSendTimestamp();

    /**
     * Get read timestamp
     *
     * @return int Read UNIX timestamp or null if never been read
     */
    public function getReadTimestamp();

    /**
     * Get message type
     *
     * @return string Message type or null if none set
     */
    public function getType();

    /**
     * Get message contents
     *
     * @return mixed Data set by the sender
     */
    public function getContents();

    /**
     * Get message level
     *
     * Message level is an arbitrary integer value which can have any purpose
     * in the upper business value. It doesn't alter the default behavior.
     *
     * @return int Arbitrary level set in queue
     */
    public function getLevel();

    /**
     * Get subscription identifier
     *
     * Use this method rather than getSubscription() whenever possible, in
     * order to avoid backends lookup in most implementations
     *
     * @return mixed Subscription identifier
     */
    public function getSubscriptionId();

    /**
     * Get channel
     *
     * @return \APubSub\SubscriptionInterface
     */
    public function getSubscription();

    /**
     * Get channel identifier
     *
     * Use this method rather than getChannel() whenever possible, in order to
     * avoid backends lookup in most implementations
     *
     * @return string
     */
    public function getChannelId();

    /**
     * Get channel
     *
     * @return \APubSub\ChannelInterface
     */
    public function getChannel();
}
