<?php

namespace APubSub\Memory;

use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\PubSubInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemoryPubSub extends AbstractMemoryObject implements PubSubInterface
{
    public function __construct()
    {
        $this->setContext(new MemoryContext());
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
        if (!isset($this->context->channels[$id])) {
            throw new ChannelDoesNotExistException();
        }

        return $this->context->channels[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannel()
     */
    public function createChannel($id)
    {
        if (isset($this->context->channels[$id])) {
            throw new ChannelAlreadyExistsException();
        }

        return $this->context->channels[$id] = new MemoryChannel($id, $this);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::listChannels()
     */
    public function listChannels($limit, $offset)
    {
        $iterator = new \LimitIterator(
            new ArrayIterator($this->context->channels), $offset, $limit);

        return iterator_to_array($iterator);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        $channel = $this->getChannel($id);

        foreach ($this->context->subscriptions as $index => $subscription) {
            if ($subscription->getChannel()->getId() === $id) {
                unset($this->context->subscriptions[$index]);
            }
        }
        $this->context->subscriptions = array_filter($this->context->subscriptions);

        unset($this->context->channels[$id]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscription()
     */
    public function getSubscription($id)
    {
        if (!isset($this->context->subscriptions[$id])) {
            throw new SubscriptionDoesNotExistException();
        }

        return $this->context->subscriptions[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscription()
     */
    public function deleteSubscription($id)
    {
        $this->getSubscription($id);

        unset($this->context->subscriptions[$id]);

        // FIXME: Delete subscription id from message queue
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
     * @see \APubSub\PubSubInterface::getSubscriber()
     */
    public function getSubscriber($id)
    {
        if (!isset($this->context->subscribers[$id])) {
            $this->context->subscribers[$id] = new MemorySubscriber($id, $this);
        }

        return $this->context->subscribers[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::garbageCollection()
     */
    public function garbageCollection()
    {
    }
}
