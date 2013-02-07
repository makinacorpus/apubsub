<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\AbstractObject;
use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Error\UncapableException;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemoryChannel extends AbstractObject implements ChannelInterface
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
     * @see \APubSub\ChannelInterface::createMessage()
     */
    public function send($contents, $type = null, $level = 0, $sendTime = null)
    {
        if (null === $sendTime) {
            $sendTime = time();
        }

        $msgId = $this->context->getNextMessageIdentifier();

        $subscriptionIdList = array();
        foreach ($this->context->subscriptions as $subscriptionId => $subscription) {
            if ($subscription->getChannel()->getId() === $this->id &&
                $subscription->isActive())
            {
                $message = new MemoryMessage(
                    $this->context,
                    $this->id,
                    $subscriptionId,
                    $contents,
                    $msgId,
                    $sendTime,
                    $type,
                    true,
                    null,
                    $level);

                $this->context->subscriptionMessages[$subscriptionId][$msgId] = $message;
            }
        }

        $message = new MemoryMessage(
            $this->context,
            $this->id,
            null,
            $contents,
            $msgId,
            $sendTime,
            $type,
            true,
            null,
            $level);

        $this->context->channelMessages[$this->id][$msgId] = $message;

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
        if (isset($this->context->channelMessages[$this->id])) {
            foreach ($idList as $id) {
                unset($this->context->channelMessages[$this->id][$id]);
            }
        }

        // Also remove in subscriptions
        foreach ($this->context->subscriptions as $subscription) {
            $subscription->deleteMessages($idList);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessage()
     */
    public function getMessage($id)
    {
        if (!isset($this->context->channelMessages[$this->id][$id])) {
            throw new MessageDoesNotExistException();
        }

        return $this->context->channelMessages[$this->id][$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessages(array $idList)
    {
        $ret = array();

        foreach ($idList as $id) {
            $ret[] = $this->getMessage($id);
        }

        return $ret;
    }
}
