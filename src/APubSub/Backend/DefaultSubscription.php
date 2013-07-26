<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\CursorInterface;
use APubSub\SubscriptionInterface;

abstract class DefaultSubscription extends AbstractMessageContainer implements
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
    // @todo Switch back to private once no other implementations exists
    protected $active = false;

    /**
     * Time when this subscription has been activated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    // @todo Switch back to private once no other implementations exists
    protected $activatedTime;

    /**
     * Time when this subscription has been deactivated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    // @todo Switch back to private once no other implementations exists
    protected $deactivatedTime;

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
            CursorInterface::FIELD_SUB_ID => $id,
        ));

        $this->id = $id;
        $this->chanId = $chanId;
        $this->created = $created;
        $this->activatedTime = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
        $this->active = $isActive;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getChannelId()
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getChannel()
     */
    public function getChannel()
    {
        return $this
            ->context
            ->getBackend()
            ->getChannel($this->chanId);
    }

    /**
     * (non-PHPdoc)
     * @see APubSub.ChannelInterface::getCreationTime()
     */
    public function getCreationTime()
    {
        return $this->created;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::isActive()
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getStartTime()
     */
    public function getStartTime()
    {
        if (!$this->active) {
            throw new \LogicException("This subscription is not active");
        }

        return $this->activatedTime;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::getStopTime()
     */
    public function getStopTime()
    {
        if ($this->active) {
            throw new \LogicException("This subscription is active");
        }

        return $this->deactivatedTime;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::deactivate()
     */
    abstract public function deactivate();

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::activate()
     */
    abstract public function activate();

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::setUnread()
     */
    abstract public function setUnread($messageId, $toggle = false);
}
