<?php

namespace APubSub\Memory;

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
     * Current backend
     *
     * @var \APubSub\Memory\MemoryPubSub
     */
    private $backend;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    private $created;

    /**
     * Internal constructor
     *
     * @param string $id            Channel identifier
     * @param MemoryPubSub $backend Backend
     */
    public function __construct($id, MemoryPubSub $backend)
    {
        $this->id = $id;
        $this->backend = $backend;
        $this->created = time();

        $this->setContext($backend->getContext());
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
     * @see \APubSub\ChannelInterface::getBackend()
     */
    public function getBackend()
    {
        return $this->backend;
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

        $message = new MemoryMessage($this, $contents, $id, $sendTime);

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
     * @see \APubSub\ChannelInterface::sendMultiple()
     */
    public function massSend($contentList)
    {
        foreach ($contentList as $contents) {
            $this->send($contents);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        $id           = $this->context->getNextSubscriptionIdentifier();
        $subscription = new MemorySubscription($this, $id);

        $this->context->subscriptions[$id] = $subscription;

        return $subscription;
    }
}
