<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\MessageInstanceInterface;

/**
 * Represent a single notification, embedding a message
 */
interface NotificationInterface extends \ArrayAccess
{
    /**
     * Info
     */
    const LEVEL_INFO = 0;

    /**
     * Notice
     */
    const LEVEL_NOTICE = 1;

    /**
     * Warning
     */
    const LEVEL_WARNING = 10;

    /**
     * Get resource type
     *
     * @return string
     */
    public function getResourceType();

    /**
     * Get resource identifiers
     *
     * @return int[]|string[]
     */
    public function getResourceIdList();

    /**
     * Get action being done done
     *
     * @return string
     */
    public function getAction();

    /**
     * Get arbitrary set data when message was sent
     *
     * @return mixed Arbitrary set data
     */
    public function getData();

    /**
     * Get original message instance
     *
     * @return MessageInstanceInterface
     */
    public function getMessage();

    /**
     * Get original message identifier
     *
     * @return scalar Message identifier
     */
    public function getMessageId();

    /**
     * Format this notification
     *
     * @return string
     */
    public function format();

    /**
     * Get an URI where to point the link over the notification
     *
     * @return string
     *   URI or null if no revelant
     */
    public function getURI();

    /**
     * Get image URI if any
     *
     * @return string
     *   Image URI or null if none
     */
    public function getImageURI();

    /**
     * Get arbitrary message level
     *
     * @return int Arbitrary message level
     */
    public function getLevel();
}
