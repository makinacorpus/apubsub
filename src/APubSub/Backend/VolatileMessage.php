<?php

namespace APubSub\Backend;

use APubSub\MessageInterface;

/**
 * In-memory volatile message
 *
 * Use this if you need a mock implementation of MessageInterface.
 */
final class VolatileMessage implements MessageInterface
{
    /**
     * @var bool
     */
    private $unread = true;

    /**
     * @var int
     */
    private $created;

    /**
     * @var int
     */
    private $read;

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $contents;

    /**
     * Default constructor.
     */
    public function __construct($contents, $type = null)
    {
        $this->read     = $this->created = time();
        $this->type     = $type;
        $this->contents = $contents;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ObjectInterface::getContext()
     */
    public function getContext()
    {
        throw new \LogicException(
            "Trying to access internals of a volatile message");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getId()
     */
    public function getId()
    {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::isUnread()
     */
    public function isUnread()
    {
        return $this->unread;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::setUnread()
     */
    public function setUnread($toggle = true)
    {
        if (!$this->unread = $toggle) {
            $this->read = time();
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSendTimestamp()
     */
    public function getSendTimestamp()
    {
        return $this->created; 
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getReadTimestamp()
     */
    public function getReadTimestamp()
    {
        return $this->read;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getType()
     */
    public function getType()
    {
        return $this->type;
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
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getLevel()
     */
    public function getLevel()
    {
        return 0;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSubscriptionId()
     */
    public function getSubscriptionId()
    {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSubscription()
     */
    public function getSubscription()
    {
        throw new \LogicException(
            "Trying to access internals of a volatile message");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getChannelId()
     */
    public function getChannelId()
    {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getChannel()
     */
    public function getChannel()
    {
        throw new \LogicException(
            "Trying to access internals of a volatile message");
    }
}
