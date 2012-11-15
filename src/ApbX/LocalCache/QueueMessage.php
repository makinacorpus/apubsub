<?php

namespace ApbX\LocalCache;

use APubSub\MessageInterface;

/**
 * Cloned messages that will live into locally cached queues
 *
 * Those messages are context agnostic and cannot retrieve channel or backend
 * instance: any access on those data will raise exceptions
 */
class QueueMessage implements MessageInterface
{
    protected $chandId;

    protected $id;

    protected $sendTimestamp;

    protected $contents;

    protected $owner;

    protected $read = false;

    /**
     * Build an instance from an existing message
     *
     * @param MessageInterface $message Message to duplicate
     */
    public function __construct(MessageInterface $message, MessageQueueInterface $owner)
    {
        $this->chandId       = $message->getChannelId();
        $this->id            = $message->getId();
        $this->sendTimestamp = $message->getSendTimestamp();
        $this->contents      = $message->getContents();
        $this->owner         = $owner;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ObjectInterface::getContext()
     */
    public function getContext()
    {
        throw new \LogicException(
            "Cached queue messages cannot have a context");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelAwareInterface::getChannelId()
     */
    public function getChannelId()
    {
        return $this->chandId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelAwareInterface::getChannel()
     */
    public function getChannel()
    {
        throw new \LogicException(
            "Cached queue messages cannot have a context therefore cannot load the channel");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSendTimestamp()
     */
    public function getSendTimestamp()
    {
        return $this->sendTimestamp;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getContents()
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Set owner queue
     *
     * @param MessageQueueInterface $owner Queue
     */
    public function setOwner(MessageQueueInterface $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get owner queue
     *
     * @return \ApbX\LocalCache\MessageQueueInterface Queue
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Has this instance been read
     *
     * @return bool True if this instance has not been read
     */
    public function isUnread()
    {
        return !$this->read;
    }

    /**
     * Mark or unmark this instance as read
     *
     * @param bool $toggle Set to false to mark as unread
     */
    public function setReadStatus($toggle = true)
    {
        if ($toggle !== $this->read) {

            $this->read = $toggle;

            if (null !== $this->owner) {
                $this->owner->setModified();
            }
        }
    }

    /**
     * On serialize make sure the owner reference is removed
     *
     * @see \ApbX\LocalCache\LRUMessageQueue::__wakeup()
     */
    public function __sleep()
    {
        return array(
            'chandId',
            'id',
            'sendTimestamp',
            'contents',
            'read',
        );
    }
}
