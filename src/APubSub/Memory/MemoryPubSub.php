<?php

namespace APubSub\Memory;

use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\PubSubInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemoryPubSub implements PubSubInterface
{
    /**
     * Array of channels
     *
     * @var array
     */
    protected $channels = array();

    /**
     * Array of subscriptions
     *
     * @var array
     */
    protected $subscriptions = array();

    /**
     * Message identifiers sequence
     *
     * @var int
     */
    protected $messageIdSeq = 0;

    /**
     * Subscriptions identifiers sequence
     *
     * @var int
     */
    protected $subscriptionIdSeq = 0;

    /**
     * Get next message identifier
     *
     * @return int
     */
    public function getNextMessageIdentifier()
    {
        return ++$this->messageIdSeq;
    }

    /**
     * Get next message identifier
     *
     * @return int
     */
    public function getNextSubscriptionIdentifier()
    {
        return ++$this->subscriptionIdSeq;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::setOptions()
     */
    public function setOptions(array $options)
    {
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannel()
     */
    public function getChannel($id)
    {
        if (!isset($this->channels[$id])) {
            throw new ChannelDoesNotExistException();
        }

        return $this->channels[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannel()
     */
    public function createChannel($id)
    {
        if (isset($this->channels[$id])) {
            throw new ChannelAlreadyExistsException();
        }

        return $this->channels[$id] = new MemoryChannel($id, $this);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::listChannels()
     */
    public function listChannels($limit, $offset)
    {
        $iterator = new \LimitIterator(
            new ArrayIterator($this->channels), $offset, $limit);

        return iterator_to_array($iterator);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        $channel = $this->getChannel($id);

        foreach ($this->subscriptions as $index => $subscription) {
            if ($subscription->getChannel()->getId() === $id) {
                unset($this->subscriptions[$index]);
            }
        }
        $this->subscriptions = array_filter($this->subscriptions);

        unset($this->channels[$id]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscription()
     */
    public function getSubscription($id)
    {
        if (!isset($this->subscriptions[$id])) {
            throw new SubscriptionDoesNotExistException();
        }

        return $this->subscriptions[$id];
    }

    /**
     * For internal use only: add a newly created subscription
     *
     * @param MemorySubscription $subscription The new subscription
     */
    public function addSubscription(MemorySubscription $subscription)
    {
        $this->subscriptions[$subscription->getId()] = $subscription;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscription()
     */
    public function deleteSubscription($id)
    {
        $this->getSubscription($id);

        unset($this->subscriptions[$id]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscriptions()
     */
    public function deleteSubscriptions($idList)
    {
        foreach ($idList as $id) {
            $this->deleteSubscription($id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::garbageCollection()
     */
    public function garbageCollection()
    {
    }
}
