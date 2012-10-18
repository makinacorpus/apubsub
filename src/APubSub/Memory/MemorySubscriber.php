<?php

namespace APubSub\Memory;

use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\SubscriberInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemorySubscriber extends AbstractMemoryObject implements
    SubscriberInterface
{
    /**
     * User set identifier
     *
     * @var scalar
     */
    private $id;

    /**
     * Current subscriptions
     *
     * @var array
     */
    private $subscriptions = array();

    /**
     * @var \APubSub\Memory\MemoryPubSub
     */
    private $backend;

    public function __construct($id, MemoryPubSub $backend)
    {
        $this->id = $id;
        $this->backend = $backend;

        $this->setContext($backend->getContext());
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptions()
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptionFor()
     */
    public function getSubscriptionFor($channelId)
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getChannel()->getId() === $channelId) {
                return $subscription;
            }
        }

        throw new SubscriptionDoesNotExistException();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::subscribe()
     */
    public function subscribe($channelId)
    {
        $channel = $this->backend->getChannel($channelId);

        // Remember, this is for testing purposes only
        try {
            if ($this->getSubscriptionFor($channelId)) {
                throw new SubscriptionAlreadyExistsException();
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // Everything is OK
        }

        $subscription = $channel->subscribe();
        $subscription->activate();

        $this->subscriptions[$subscription->getId()] = $subscription;

        return $subscription;
    }

    /**
     * Fetch oldest messages in queue all active subscriptions included
     *
     * @param int $limit Number of messages to fetch
     *
     * @return array     List of MessageInterface instances ordered by ascending
     *                   creation timestamp
     */
    public function fetchHead($limit)
    {
        return $this->context->getMessageListFor($idList, $limit, false);
    }

    /**
     * Fetch latest messages in queue all active subscriptions included
     *
     * @param int $limit Number of messages to fetch
     *
     * @return array     List of MessageInterface instances ordered by
     *                   descending creation timestamp
     */
    public function fetchTail($limit)
    {
        $idList = array();
        foreach ($this->subscriptions as $subscription) {
            $idList[] = $subscription->getId();
        }

        return $this->context->getMessageListFor($idList, $limit, true);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetch()
     */
    public function fetch()
    {
        $idList = array();
        foreach ($this->subscriptions as $subscription) {
            $idList[] = $subscription->getId();
        }

        return $this->context->getMessageListFor($idList);
    }
}
