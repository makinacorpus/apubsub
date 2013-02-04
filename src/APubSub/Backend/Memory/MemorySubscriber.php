<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\AbstractObject;
use APubSub\Backend\ArrayCursor;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\CursorInterface;
use APubSub\SubscriberInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemorySubscriber extends AbstractObject implements SubscriberInterface
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
     * Default constructor
     *
     * @param MemoryContext $context Context
     * @param scalar $id             Identifier
     */
    public function __construct(MemoryContext $context, $id)
    {
        $this->id = $id;
        $this->context = $context;
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
        $channel = $this->context->backend->getChannel($channelId);

        try {
            return $this->getSubscriptionFor($channelId);
        } catch (SubscriptionDoesNotExistException $e) {
            $subscription = $channel->subscribe();
            $subscription->activate();

            $this->subscriptions[$subscription->getId()] = $subscription;
        }

        return $subscription;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::subscribe()
     */
    public function unsubscribe($channelId)
    {
        try {
            $this->getSubscriptionFor($channelId)->delete();
        } catch (SubscriptionDoesNotExistException $e) {
            return;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetch()
     */
    public function fetch(array $conditions = null)
    {
        $ret = array();

        foreach ($this->getSubscriptions() as $subscription) {
            foreach ($subscription->fetch() as $message) {
                $ret[] = $message;
            }
        }

        if ($conditions) {
            $ret = array_filter($ret, function ($a) use ($conditions) {
                return MemorySubscription::filterMessages($a, $conditions);
            });
        }

        $sorter = new MemoryMessageSorter();

        return new ArrayCursor($this->context, $ret, $sorter->getAvailableSorts(), $sorter);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::flush()
     */
    public function flush()
    {
        foreach ($this->getSubscriptions() as $subscription) {
            $subscription->flush();
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessage()
     */
    public function deleteMessage($id)
    {
        $this->deleteMessages(array($id));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessages()
     */
    public function deleteMessages(array $idList)
    {
        // Also remove in subscriptions
        foreach ($this->getSubscriptions() as $subscription) {
            $subscription->deleteMessages($idList);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessage()
     */
    public function getMessage($id)
    {
        foreach ($this->getSubscriptions() as $subscription) {
            foreach ($subscription->fetch() as $message) {
                if ($message->getId() === $id) {
                    return $message;
                }
            }
        }

        throw new MessageDoesNotExistException();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessages(array $idList)
    {
        $ret = array();

        foreach ($this->getSubscriptions() as $subscription) {
            foreach ($subscription->fetch() as $message) {
                if (in_array($id, $message->getId())) {
                    return $ret[] = $message;
                }
            }
        }

        if (count($ret) !== count($idList)) {
            throw new MessageDoesNotExistException();
        }

        // FIXME Re-order messages following the $idList order

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::delete()
     */
    public function delete()
    {
        foreach ($this->getSubscriptions() as $subscription) {
            $subscription->delete();
        }
    }
}
