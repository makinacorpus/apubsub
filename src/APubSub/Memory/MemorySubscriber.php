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
        throw new \Exception("Not implemented yet");
    }

    /**
     * Fetch latest messages in queue all active subscriptions included
     *
     * @param int $limit Number of messages to fetch
     *
     * @return array     List of MessageInterface instances ordered by ascending
     *                   creation timestamp
     */
    public function fetchTail($limit)
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Fetch all messages in queue all active subscriptions included
     *
     * @return array List of MessageInterface instances ordered by ascending
     *               creation timestamp
     */
    public function fetch()
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * For internal use only sort a message list by created time asc (callback
     * for asort)
     */
    public function sortMessagesByCreationAscCallback($a, $b)
    {
        return $a->getCreationTime() - $b->getCreationTime();
    }
}
