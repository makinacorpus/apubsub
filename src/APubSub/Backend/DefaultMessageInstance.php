<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\MessageInstanceInterface;

class DefaultMessageInstance extends DefaultMessage implements
    MessageInstanceInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    private $queueId;

    /**
     * Send time UNIX timestamp
     *
     * @var int
     */
    private $sendTime;

    /**
     * Is this message unread
     *
     * @var bool
     */
    private $unread = true;

    /**
     * Read timestamp
     *
     * @var int
     */
    private $readTimestamp;

    /**
     * Subscription identifier
     *
     * @var string
     */
    private $subscriptionId;

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param string $subscriptionId    Subscription identifier
     * @param mixed $contents           Message contents
     * @param scalar $id                Message identifier
     * @param scalar $queueId           Queue identifier
     * @param int $sendTime             Send time UNIX timestamp
     * @param string $type              Message type
     * @param bool $isUnread            Is this message unread
     * @param int $readTimestamp        Read timestamp
     * @param int $level                Level
     */
    public function __construct(
        ContextInterface $context,
        $subscriptionId,
        $contents,
        $id,
        $queueId,
        $sendTime,
        $type          = null,
        $isUnread      = true,
        $readTimestamp = null,
        $level         = 0)
    {
        parent::__construct($context, $contents, $id, $type, $level);

        $this->queueId        = $queueId;
        $this->subscriptionId = $subscriptionId;
        $this->sendTime       = $sendTime;
        $this->unread         = $isUnread;
        $this->readTimestamp  = $readTimestamp;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInstanceInterface::getQueueId()
     */
    public function getQueueId()
    {
        return $this->queueId;
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

            if ($toggle) {
                $timestamp = null;
            } else {
                $timestamp = time();
            }

            $this
                ->context
                ->getBackend()
                ->setUnread(
                    $this->queueId,
                    $toggle);

            $this->readTimestamp = $timestamp;
            $this->unread = $toggle;
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
     * @see \APubSub\MessageInterface::getReadTimestamp()
     */
    public function getReadTimestamp()
    {
        return $this->readTimestamp;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSubscriptionId()
     */
    public function getSubscriptionId()
    {
      return $this->subscriptionId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSubscription()
     */
    public function getSubscription()
    {
        return $this
            ->context
            ->getBackend()
            ->getSubscription(
                $this->subscriptionId);
    }
}
