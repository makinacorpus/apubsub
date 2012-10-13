<?php

namespace APubSub\Memory;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Impl\DefaultMessage;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemoryChannel implements ChannelInterface
{
    /**
     * Channel identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Current backend
     *
     * @var \APubSub\Memory\MemoryPubSub
     */
    protected $backend;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    protected $created;

    /**
     * All messages reference
     *
     * @var array
     */
    protected $messages = array();

    /**
     * All subscriptions reference
     *
     * @var array
     */
    protected $subscriptions = array();

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
        if (!isset($this->messages[$id])) {
            throw new MessageDoesNotExistException();
        }

        return $this->messages[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::createMessage()
     */
    public function createMessage($contents, $sendTime = null)
    {
        return new DefaultMessage($this, $contents, null, $sendTime);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::sendMessage()
     */
    public function send(MessageInterface $message)
    {
        if (!$message instanceof DefaultMessage || $message->getChannel() !== $this) {
            throw new \LogicException(
                "You are trying to inject a message which does not originate from this channel");
        }

        $id = $this->backend->getNextMessageIdentifier();

        // This may throw an exception, and that's what we are looking for: we
        // cannot let the message message being sent more than once
        $message->setId($id);

        $this->messages[$id] = $message;

        foreach ($this->subscriptions as $subscription) {
            if ($subscription->isActive()) {
                $subscription->addMessage($message);
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ChannelInterface::subscribe()
     */
    public function subscribe()
    {
        $id = $this->backend->getNextSubscriptionIdentifier();

        $this->subscriptions[$id] = $subscription = new MemorySubscription($this, $id);
        $this->backend->addSubscription($subscription);

        return $subscription;
    }
}
