<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\MessageInterface;

/**
 * Represent a single notification, embedding a message
 *
 * Single notification is tied to its container: modification of its read
 * status must change the modified status of the container so that it can
 * be saved.
 */
class Notification implements \ArrayAccess
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
     * @var NotificationService
     */
    private $service;

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * Contents from message.
     *
     * @var array
     */
    private $data;

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
     * @param NotificationService $service    Notification service
     * @param array|MessageInterface $message Message instance or contents
     */
    public function __construct(
        NotificationService $service,
        MessageInterface $message)
    {
        $this->service = $service;
        $this->message = $message;

        if (($contents = $message->getContents()) && is_array($contents)) {

            if (isset($contents['f'])) {
                $this->formatted = $contents['f'];
            }
            if (isset($contents['d'])) {
                $this->data = $contents['d'];
            } else {
                $this->data = array();
            }
        }
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
        return $this->message->getId();
    }

    /**
     * Get notification type
     *
     * @return string Notification type
     */
    public function getType()
    {
        return $this->message->getType();
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
        if (null === $this->formatted) {
            $this->formatted = $this
                ->service
                ->getFormatterRegistry()
                ->getInstance($this->message->getType())
                ->format($this)
            ;
        }

        return $this->formatted;
    }

    /**
     * Get image URI if any
     *
     * @return string Image URI or null if none
     */
    public function getImageUri()
    {
        return $this
            ->service
            ->getFormatterRegistry()
            ->getInstance($this->message->getType())
            ->getImageURI($this)
        ;
    }

    /**
     * Get arbitrary message level
     *
     * @return int Arbitrary message level
     */
    public function getLevel()
    {
        return $this->message->getLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        // Avoid some PHP warnings, this is purely for display
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException("Notification are readonly");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException("Notification are readonly");
    }
}
