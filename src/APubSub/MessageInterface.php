<?php

namespace APubSub;

use APubSub\ObjectInterface;

/**
 * Represent a specific message tied to a subscription
 */
interface MessageInterface extends
    ObjectInterface,
    ChannelAwareInterface,
    SubscriptionAwareInterface
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
     * Get notification type
     *
     * @return string Notification type or null if none set
     */
    public function getType();

    /**
     * Get message contents
     *
     * @return mixed Data set by the sender
     */
    public function getContents();
}
