<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;
use APubSub\Field;
use APubSub\SubscriptionInterface;

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
     * Creation UNIX timestamp
     *
     * @var int
     */
    private $created;

    /**
     * Is this subscription active
     *
     * @var bool
     */
    private $active = false;

    /**
     * Time when this subscription has been activated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    private $activatedTime;

    /**
     * Time when this subscription has been deactivated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    private $deactivatedTime;

    /**
     * Default constructor
     *
     * @param int $chanId
     *   Channel identifier
     * @param int $id
     *   Subscription identifier
     * @param int $created
     *   Creation UNIX timestamp
     * @param int $activatedTime
     *   Latest activation UNIX timestamp
     * @param int $deactivatedTime
     *   Latest deactivation UNIX timestamp
     * @param bool $isActive
     *   Is this subscription active
     * @param BackendInterface $backend
     *   Backend
     */
    public function __construct(
        $chanId,
        $id,
        $created,
        $activatedTime,
        $deactivatedTime,
        $isActive,
        BackendInterface $backend)
    {
        parent::__construct($backend, [Field::SUB_ID => $id]);

        $this->id = $id;
        $this->chanId = $chanId;
        $this->created = $created;
        $this->activatedTime = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
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
    public function getCreationTime()
    {
        return $this->created;
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
    public function getStartTime()
    {
        if (!$this->active) {
            throw new \LogicException("This subscription is not active");
        }

        return $this->activatedTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getStopTime()
    {
        if ($this->active) {
            throw new \LogicException("This subscription is active");
        }

        return $this->deactivatedTime;
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate()
    {
        $deactivated = time();

        $this
            ->getBackend()
            ->fetchSubscriptions([
                Field::SUB_ID => $this->id,
            ])
            ->update([
                Field::SUB_STATUS => 0,
                Field::SUB_DEACTIVATED => $deactivated,
            ])
        ;

        $this->active = false;
        $this->deactivatedTime = $deactivated;
    }

    /**
     * {@inheritdoc}
     */
    public function activate()
    {
        $activated = time();

        $this
            ->getBackend()
            ->fetchSubscriptions([
                Field::SUB_ID => $this->id,
            ])
            ->update([
                Field::SUB_STATUS => 1,
                Field::SUB_DEACTIVATED => $activated,
            ])
        ;

        $this->active = true;
        $this->activatedTime = $activated;
    }
}
