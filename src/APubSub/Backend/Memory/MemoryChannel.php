<?php

namespace APubSub\Backend\Memory;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemoryChannel extends AbstractMemoryObject implements ChannelInterface
{
    /**
     * Channel identifier
     *
     * @var string
     */
    private $id;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    private $created;

    /**
     * Internal constructor
     *
     * @param MemoryContext $context Context
     * @param string $id             Channel identifier
     */
    public function __construct(MemoryContext $context, $id)
    {
        $this->id = $id;
        $this->created = time();
        $this->context = $context;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getId()
     */
    public function getId()
    {
        return $this->id;
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
     * @see \APubSub\ChannelInterface::getMessage()
     */
    public function getMessage($id)
    {
        if (!isset($this->context->messages[$id])) {
            throw new MessageDoesNotExistException();
        }

        $message = $this->context->messages[$id];

        if ($message->getChannel()->getId() !== $this->id) {
            throw new MessageDoesNotExistException();
        }

        return $message;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::getMessages()
     */
    public function getMessages($idList)
    {
        $ret = array();

        foreach ($idList as $id) {
            $ret[] = $this->getMessage($id);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::createMessage()
     */
    public function send($contents, $sendTime = null)
    {
        if (null === $sendTime) {
            $sendTime = time();
        }

        $id = $this->context->getNextMessageIdentifier();

        $message = new MemoryMessage($this->context, $this->id, $contents, $id, $sendTime);

        $subscriptionIdList = array();
        foreach ($this->context->subscriptions as $subscription) {
            if ($subscription->getChannel()->getId() === $this->id &&
                $subscription->isActive())
            {
                $subscriptionIdList[] = $subscription->getId();
            }
        }

        $message->setSubscriptionIds($subscriptionIdList);
        $this->context->addMessage($message);

        return $message;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        $id           = $this->context->getNextSubscriptionIdentifier();
        $subscription = new MemorySubscription($this->context, $this->id, $id);

        $this->context->subscriptions[$id] = $subscription;

        return $subscription;
    }
}
