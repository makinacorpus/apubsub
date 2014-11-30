<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;
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
     * @param BackendInterface $backend
     *   Backend
     * @param string $subscriptionId
     *   Subscription identifier
     * @param mixed $contents
     *   Message contents
     * @param scalar $id
     *   Message identifier
     * @param scalar $queueId
     *   Queue identifier
     * @param int $sendTime
     *   Send time UNIX timestamp
     * @param string $type
     *   Message type
     * @param bool $isUnread
     *   Is this message unread
     * @param int $readTimestamp
     *   Read timestamp
     * @param int $level
     *   Level
     */
    public function __construct(
        BackendInterface $backend,
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
        parent::__construct($backend, $contents, $id, $type, $level);

        $this->queueId        = $queueId;
        $this->subscriptionId = $subscriptionId;
        $this->sendTime       = $sendTime;
        $this->unread         = $isUnread;
        $this->readTimestamp  = $readTimestamp;
    }

    public function getQueueId()
    {
        return $this->queueId;
    }

    public function isUnread()
    {
        return $this->unread;
    }

    public function setUnread($toggle = false)
    {
        if ($this->unread !== $toggle) {

            if ($toggle) {
                $timestamp = null;
            } else {
                $timestamp = time();
            }

            $this
                ->getBackend()
                ->setUnread(
                    $this->queueId,
                    $toggle);

            $this->readTimestamp = $timestamp;
            $this->unread = $toggle;
        }
    }

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

    public function getReadTimestamp()
    {
        return $this->readTimestamp;
    }

    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    public function getSubscription()
    {
        return $this
            ->getBackend()
            ->getSubscription(
                $this->subscriptionId);
    }
}
