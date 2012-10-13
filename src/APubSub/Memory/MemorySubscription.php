<?php

namespace APubSub\Memory;

use APubSub\Impl\DefaultMessage;
use APubSub\SubscriptionInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemorySubscription implements SubscriptionInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    protected $id;

    /**
     * Channel this message belongs to
     *
     * @var \APubSub\MemoryChannel
     */
    protected $channel;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    protected $created;

    /**
     * Is this subscription active
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Time when this subscription has been activated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    protected $activatedTime;

    /**
     * Time when this subscription has been deactivated for the last time as a
     * UNIX timestamp
     *
     * @var int
     */
    protected $deactivatedTime;

    /**
     * Current message queue
     *
     * @var array
     */
    protected $messageQueue = array();

    /**
     * Already fetched messages
     *
     * @var array
     */
    protected $readMessages = array();

    /**
     * Default constructor
     *
     * @param ChannelInterface $channel Channel this message belongs to
     * @param scalar $id                Message identifier
     */
    public function __construct(MemoryChannel $channel, $id = null)
    {
        $this->id = $id;
        $this->channel = $channel;
        $this->created = $this->deactivatedTime = time();
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
     * @see \APubSub\SubscriptionInterface::getChannel()
     */
    public function getChannel()
    {
        return $this->channel;
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
     * @see \APubSub\SubscriptionInterface::delete()
     */
    public function delete()
    {
        $this->getChannel()->getBackend()->deleteSubscription($this->getId());
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::fetch()
     */
    public function fetch()
    {
        // Keep older message, no feature is yet stubbed for this but this will
        // be later
        $this->readMessages += $this->messageQueue;

        $ret = $this->messageQueue;
        $this->messageQueue = array();

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::deactivate()
     */
    public function deactivate()
    {
        $this->active = false;
        $this->deactivatedTime = time();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::activate()
     */
    public function activate()
    {
        $this->active = true;
        $this->activatedTime = time();
    }

    /**
     * For internal use only: add a message to this subscription
     */
    public function addMessage(DefaultMessage $message)
    {
        $this->messageQueue[$message->getId()] = $message;
    }
}
