<?php

namespace Apb\Notification;

use APubSub\MessageInterface;

/**
 * Single notification is tied to its container: modification of its read
 * status must change the modified status of the container so that it can
 * be saved.
 */
class Notification
{
    /**
     * @var NotificationManager
     */
    private $manager;

    /**
     * Contents from message.
     *
     * @var array
     */
    private $data;

    /**
     * @var scalar
     */
    private $sourceId;

    /**
     * Contents contains the appropriate data
     *
     * @var bool
     */
    private $valid = false;

    /**
     * Notification type
     *
     * @var string
     */
    private $type;

    /**
     * @var scalar
     */
    private $messageId;

    /**
     * Build instance from message
     *
     * @param NotificationManager $manager Notification manager
     * @param MessageInterface $message    Message
     */
    public function __construct(
        NotificationManager $manager,
        MessageInterface $message)
    {
        $this->manager = $manager;

        if (($contents = $message->getContents()) &&
            is_array($contents) &&
            isset($contents['i']) &&
            isset($contents['d']))
        {
            $this->data      = $contents['d'];
            $this->sourceId  = $contents['i'];
            $this->type      = $message->getType();
            $this->messageId = $message->getId();
            $this->valid     = true;
        } else {
            $this->data = array();
        }
    }

    /**
     * Tell if this instance has a valid message
     *
     * @return boolean True if valid
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Get arbitrary set data when message was sent
     *
     * @return mixed Arbitrary set data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get original message identifier
     *
     * @return scalar Message identifier
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Get notification source identifier
     *
     * @return mixed Arbitrary source identifier
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * Get notification type
     *
     * @return string Notification type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get arbitrary value from arbitrary data
     *
     * @param string $key Value key
     */
    public function get($key)
    {
        if (is_array($this->data)) {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        } else {
            return null;
        }
    }

    /**
     * Format this notification
     *
     * @return string|array drupal_render() friendly structure
     */
    public function format()
    {
        return $this
            ->manager
            ->getTypeRegistry()
            ->getInstance($this->type)
            ->format($this);
    }

    /**
     * Get image URI if any
     *
     * @return string Image URI or null if none
     */
    public function getImageUri()
    {
        return $this
            ->manager
            ->getTypeRegistry()
            ->getInstance($this->type)
            ->getImageURI($this);
    }
}
