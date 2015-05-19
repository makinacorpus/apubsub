<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;
use APubSub\Field;
use APubSub\SubscriptionInterface;
use APubSub\Misc;

class DefaultSubscription extends AbstractMessageContainer implements
    SubscriptionInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Channel database identifier
     *
     * @var string
     */
    private $chanId;

    /**
     * Creation date
     *
     * @var \DateTime
     */
    private $createdAt;

    /**
     * Is this subscription active
     *
     * @var bool
     */
    private $active = false;

    /**
     * Date when this subscription has been activated for the last time
     *
     * @var \DateTime
     */
    private $activatedAt;

    /**
     * Date when this subscription has been deactivated for the last time
     *
     * @var \DateTime
     */
    private $deactivatedAt;

    /**
     * Default constructor
     *
     * @param int $chanId
     *   Channel identifier
     * @param int $id
     *   Subscription identifier
     * @param \DateTime $createdAt
     *   Creation date
     * @param \DateTime $activatedAt
     *   Latest activation date
     * @param \DateTime $deactivatedAt
     *   Latest deactivation date
     * @param bool $isActive
     *   Is this subscription active
     * @param BackendInterface $backend
     *   Backend
     */
    public function __construct(
        $chanId,
        $id,
        \DateTime $createdAt,
        \DateTime $activatedAt,
        \DateTime $deactivatedAt,
        $isActive,
        BackendInterface $backend)
    {
        parent::__construct($backend, [Field::SUB_ID => $id]);

        $this->id = $id;
        $this->chanId = $chanId;
        $this->createdAt = $createdAt;
        $this->activatedAt = $activatedAt;
        $this->deactivatedAt = $deactivatedAt;
        $this->active = $isActive;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannel()
    {
        return $this
            ->getBackend()
            ->getChannel($this->chanId)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationDate()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartDate()
    {
        if (!$this->active) {
            throw new \LogicException("This subscription is not active");
        }

        return $this->activatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getStopDate()
    {
        if ($this->active) {
            throw new \LogicException("This subscription is active");
        }

        return $this->deactivatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate()
    {
        $deactivated = new \DateTime();

        $this
            ->getBackend()
            ->fetchSubscriptions([
                Field::SUB_ID => $this->id,
            ])
            ->update([
                Field::SUB_STATUS => 0,
                Field::SUB_DEACTIVATED => $deactivated->format(Misc::SQL_DATETIME),
            ])
        ;

        $this->active = false;
        $this->deactivatedAt = $deactivated;
    }

    /**
     * {@inheritdoc}
     */
    public function activate()
    {
        $activated = new \DateTime();

        $this
            ->getBackend()
            ->fetchSubscriptions([
                Field::SUB_ID => $this->id,
            ])
            ->update([
                Field::SUB_STATUS => 1,
                Field::SUB_DEACTIVATED => $activated->format(Misc::SQL_DATETIME),
            ])
        ;

        $this->active = true;
        $this->activatedAt = $activated;
    }
}
