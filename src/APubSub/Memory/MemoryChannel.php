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

        $id = $this->backend->getNextMessageIdentifier();

        $message = new DefaultMessage($this, $contents, $id, $sendTime);

        $this->messages[$id] = $message;

        foreach ($this->subscriptions as $subscription) {
            if ($subscription->isActive()) {
                $subscription->addMessage($message);
            }
        }

        return $message;
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
