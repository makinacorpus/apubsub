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
     * Channel identifier
     *
     * @var string
     */
    private $chanId;

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
     * @param string $chanId            Channel identifier
     * @param string $subscriptionId    Subscription identifier
     * @param mixed $contents           Message contents
     * @param scalar $id                Message identifier
     * @param int $sendTime             Send time UNIX timestamp
     * @param string $type              Message type
     * @param bool $isUnread            Is this message unread
     * @param int $readTimestamp        Read timestamp
     * @param int $level                Level
     */
    public function __construct(
        ContextInterface $context,
        $chanId,
        $subscriptionId,
        $contents,
        $id,
        $sendTime,
        $type          = null,
        $isUnread      = true,
        $readTimestamp = null,
        $level         = 0)
    {
        parent::__construct($context, $contents, $id, $type, $level);

        $this->chanId         = $chanId;
        $this->subscriptionId = $subscriptionId;
        $this->sendTime       = $sendTime;
        $this->unread         = $isUnread;
        $this->readTimestamp  = $readTimestamp;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getId()
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
                $this->readTimestamp = null;
            } else {
                $this->readTimestamp = time();
            }

            $this
                ->context
                ->getBackend()
                ->setUnread(
                    $this->queueId,
                    $toggle);
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

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getChannelId()
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getChannel()
     */
    public function getChannel()
    {
        return $this
            ->context
            ->getBackend()
            ->getChannel(
                $this->chanId);
    }
}
