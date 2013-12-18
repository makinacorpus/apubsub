<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\SubscriptionInterface;
use APubSub\Field;

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
     * @param int $chanId          Channel identifier
     * @param int $id              Subscription identifier
     * @param int $created         Creation UNIX timestamp
     * @param int $activatedTime   Latest activation UNIX timestamp
     * @param int $deactivatedTime Latest deactivation UNIX timestamp
     * @param bool $isActive       Is this subscription active
     * @param ContextInterface     Context
     */
    public function __construct(
        $chanId,
        $id,
        $created,
        $activatedTime,
        $deactivatedTime,
        $isActive,
        ContextInterface $context)
    {
        parent::__construct($context, array(
            Field::SUB_ID => $id,
        ));

        $this->id = $id;
        $this->chanId = $chanId;
        $this->created = $created;
        $this->activatedTime = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
        $this->active = $isActive;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getChannelId()
    {
        return $this->chanId;
    }

    public function getChannel()
    {
        return $this
            ->context
            ->getBackend()
            ->getChannel($this->chanId);
    }

    public function getCreationTime()
    {
        return $this->created;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function getStartTime()
    {
        if (!$this->active) {
            throw new \LogicException("This subscription is not active");
        }

        return $this->activatedTime;
    }

    public function getStopTime()
    {
        if ($this->active) {
            throw new \LogicException("This subscription is active");
        }

        return $this->deactivatedTime;
    }

    public function deactivate()
    {
        $deactivated = time();

        $this
            ->context
            ->getBackend()
            ->fetchSubscriptions(array(
                Field::SUB_ID => $this->id,
            ))
            ->update(array(
                Field::SUB_STATUS => 0,
                Field::SUB_DEACTIVATED => $deactivated,
            ));

        $this->active = false;
        $this->deactivatedTime = $deactivated;
    }

    public function activate()
    {
        $activated = time();

        $this
            ->context
            ->getBackend()
            ->fetchSubscriptions(array(
                Field::SUB_ID => $this->id,
            ))
            ->update(array(
                Field::SUB_STATUS => 1,
                Field::SUB_DEACTIVATED => $activated,
            ));

        $this->active = true;
        $this->activatedTime = $activated;
    }
}
