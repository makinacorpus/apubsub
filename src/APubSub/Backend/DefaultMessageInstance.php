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
     * Send time date
     *
     * @var \DateTime
     */
    private $sentAt;

    /**
     * Is this message unread
     *
     * @var bool
     */
    private $unread = true;

    /**
     * Read date (can be null)
     *
     * @var \DateTime
     */
    private $readAt;

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
     * @param \DateTime $sentAt
     *   Send date
     * @param string $type
     *   Message type
     * @param bool $isUnread
     *   Is this message unread
     * @param \DateTime $readAt
     *   Read date
     * @param int $level
     *   Level
     * @param string $origin
     *   Origin
     */
    public function __construct(
        BackendInterface $backend,
        $subscriptionId,
        $contents,
        $id,
        $queueId,
        \DateTime $sentAt,
        $type               = null,
        $isUnread           = true,
        \DateTime $readAt   = null,
        $level              = 0,
        $origin             = null)
    {
        parent::__construct($backend, $contents, $id, $type, $level, $origin);

        $this->queueId          = $queueId;
        $this->subscriptionId   = $subscriptionId;
        $this->sentAt           = $sentAt;
        $this->unread           = $isUnread;
        $this->readAt           = $readAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueId()
    {
        return $this->queueId;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnread()
    {
        return $this->unread;
    }

    /**
     * {@inheritdoc}
     */
    public function setUnread($toggle = false)
    {
        if ($this->unread !== $toggle) {

            if ($toggle) {
                $readAt = null;
            } else {
                $readAt = new \DateTime();
            }

            $this
                ->getBackend()
                ->setUnread($this->queueId, $toggle)
            ;

            $this->readAt = $readAt;
            $this->unread = $toggle;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSendDate()
    {
        return $this->sentAt;
    }

    /**
     * Set send date
     *
     * @param \DateTime $sendTime
     */
    public function setSendDate($sentAt)
    {
        $this->sentAt = $sentAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getReadDate()
    {
        return $this->readAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscription()
    {
        return $this
            ->getBackend()
            ->getSubscription($this->subscriptionId)
        ;
    }
}
