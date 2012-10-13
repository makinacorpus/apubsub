<?php

namespace APubSub\Drupal7;

use APubSub\ChannelInterface;
use APubSub\Error\MessageDoesNotExistException;
use APubSub\Impl\DefaultMessage;
use APubSub\MessageInterface;

/**
 * Array based implementation for unit testing: do not use in production
 */
class D7SimpleChannel implements ChannelInterface
{
    /**
     * Channel identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Channel database identifier
     *
     * @var int
     */
    protected $dbId;

    /**
     * Current backend
     *
     * @var \APubSub\Drupal7\D7PubSub
     */
    protected $backend;

    /**
     * Creation UNIX timestamp
     *
     * @var int
     */
    protected $created;

    /**
     * Internal constructor
     *
     * @param string $id        Channel identifier
     * @param int $dbId         Channel database identifier
     * @param D7PubSub $backend Backend
     * @param int $created      Creation UNIX timestamp
     */
    public function __construct(D7PubSub $backend, $id, $dbId, $created)
    {
        $this->id = $id;
        $this->dbId = $dbId;
        $this->backend = $backend;
        $this->created = $created;
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
     * For internal use only: get database identifier
     *
     * @return int Channel database identifier
     */
    public function getDatabaseId()
    {
        return $this->dbId;
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
     * @see \APubSub\ChannelInterface::getMessage()
     */
    public function getMessage($id)
    {throw new \Exception("Not implemented yet");
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
    {throw new \Exception("Not implemented yet");
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
    {throw new \Exception("Not implemented yet");
        $id = $this->backend->getNextSubscriptionIdentifier();

        $this->subscriptions[$id] = $subscription = new MemorySubscription($this, $id);
        $this->backend->addSubscription($subscription);

        return $subscription;
    }
}
