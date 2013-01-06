<?php

namespace APubSub\Backend\Predis;

use APubSub\Backend\DefaultMessage;
use APubSub\SubscriptionInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class PredisSubscription extends AbstractPredisObject implements SubscriptionInterface
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
     * @var \APubSub\Backend\Predis\PredisChannel
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
     * Default constructor
     *
     * @param PredisChannel $channel Channel this message belongs to
     * @param int $id                Subscription identifier
     * @param int $created           Creation UNIX timestamp
     * @param int $activatedTime     Latest activation UNIX timestamp
     * @param int $deactivatedTime   Latest deactivation UNIX timestamp
     * @param bool $isActive         Is this subscription active
     */
    public function __construct(PredisChannel $channel, $id,
        $created, $activatedTime, $deactivatedTime, $isActive)
    {
        $this->id = $id;
        $this->channel = $channel;
        $this->created = $created;
        $this->activatedTime = $activatedTime;
        $this->deactivatedTime = $deactivatedTime;
        $this->active = $isActive;

        $this->setContext($channel->getContext());
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
        throw new \Exception("Not implemented yet");
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
        $client  = $this->context->client;
        $subKey  = $this->context->getKeyName(PredisContext::KEY_PREFIX_SUB . $this->id);
        $now     = time();

        $client->hmset($subKey, array(
            "active" => 0,
            "deactivated" => $now,
        ));

        // FIXME: Also clear this subscription queue
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::activate()
     */
    public function activate()
    {
        $client  = $this->context->client;
        $subKey  = $this->context->getKeyName(PredisContext::KEY_PREFIX_SUB . $this->id);
        $now     = time();

        $client->hmset($subKey, array(
            "active" => 1,
            "activated" => $now,
        ));
    }
}
