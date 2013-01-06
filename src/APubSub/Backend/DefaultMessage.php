<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\MessageInterface;
use APubSub\PubSubInterface;

/**
 * Default message implementation suitable for most backends
 */
class DefaultMessage implements MessageInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    protected $id;

    /**
     * Send time UNIX timestamp
     *
     * @var int
     */
    protected $sendTime;

    /**
     * Is this message unread
     *
     * @var bool
     */
    protected $unread = true;

    /**
     * Message raw data
     *
     * @var mixed
     */
    protected $contents;

    /**
     * Channel identifier
     *
     * @var string
     */
    protected $chanId;

    /**
     * Subscription identifier
     *
     * @var string
     */
    protected $subscriptionId;

    /**
     * @var \APubSub\ContextInterface
     */
    protected $context;

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param string $chanId            Channel identifier
     * @param string $subscriptionId    Subscription identifier
     * @param mixed $contents           Message contents
     * @param scalar $id                Message identifier
     * @param int $sendTime             Send time UNIX timestamp
     * @param bool $isUnread            Is this message unread
     */
    public function __construct(ContextInterface $context, $chanId,
        $subscriptionId, $contents, $id, $sendTime, $isUnread = true)
    {
        $this->id             = $id;
        $this->chanId         = $chanId;
        $this->subscriptionId = $subscriptionId;
        $this->contents       = $contents;
        $this->sendTime       = $sendTime;
        $this->context        = $context;
        $this->unread         = $isUnread;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ObjectInterface::getContext()
     */
    public function getContext()
    {
        return $this->context;
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
     * @see \APubSub\MessageInterface::isUnread()
     */
    public function isUnread()
    {
        return $this->unread;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::setReadStatus()
     */
    public function setUnread($toggle = false)
    {
        if ($this->unread !== $toggle) {
            $this->getSubscription()->setUnread($this->id, $toggle);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSendTimestamp()
     */
    public function getSendTimestamp()
    {
        return $this->sendTime;
    }

    /**
     * Set sent timestamp
     *
     * @param int $sendTime UNIX timestamp when the message is being sent
     */
    public function setSendTimestamp($sendTime)
    {
        $this->sendTime = $sendTime;
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
     * @see \APubSub\ChannelAwareInterface::getChannelId()
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelAwareInterface::getChannel()
     */
    public function getChannel()
    {
        return $this->context->getBackend()->getChannel($this->chanId);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionAwareInterface::getSubscriptionId()
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionAwareInterface::getSubscription()
     */
    public function getSubscription()
    {
        return $this->context->getBackend()->getSubscription($this->subscriptionId);
    }
}
