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
     * @var string
     */
    private $formatted;

    /**
     * @var int
     */
    private $level = self::LEVEL_INFO;

    /**
     * Build instance from message
     *
     * @param NotificationManager $manager    Notification manager
     * @param array|MessageInterface $message Message instance or contents
     */
    public function __construct(
        NotificationManager $manager,
        $message)
    {
        $this->manager = $manager;

        if ($message instanceof MessageInterface &&
            ($contents = $message->getContents()) &&
            is_array($contents) &&
            isset($contents['i']) &&
            isset($contents['d']))
        {
            $this->data      = $contents['d'];
            $this->sourceId  = $contents['i'];
            $this->type      = $message->getType();
            $this->messageId = $message->getId();
            $this->valid     = true;
            $this->level     = $message->getLevel();

            if (isset($contents['f'])) {
                $this->formatted = $contents['f'];
            }
        } else if (is_array($message) &&
            isset($message['i']) &&
            isset($message['d']))
        {
            $this->data      = $message['d'];
            $this->sourceId  = $message['i'];

            if (isset($message['f'])) {
                $this->formatted = $message['f'];
            }
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
        if (isset($this->formatted)) {
            return $this->formatted;
        } else {
            return $this
                ->manager
                ->getFormatterRegistry()
                ->getInstance($this->type)
                ->format($this);
        }
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
            ->getFormatterRegistry()
            ->getInstance($this->type)
            ->getImageURI($this);
    }

    /**
     * Get arbitrary message level
     *
     * @return int Arbitrary message level
     */
    public function getLevel()
    {
        return $this->level;
    }
}
