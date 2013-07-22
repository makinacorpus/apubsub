<?php

namespace APubSub\Backend\Memory;

use APubSub\ContextInterface;

/**
 * Context implementation for memory objects
 */
class MemoryContext implements ContextInterface
{
    /**
     * @var \APubSub\Backend\Memory\MemoryPubSub
     */
    public $backend;

    /**
     * Array of channels
     *
     * @var array
     */
    public $chans = array();

    /**
     * Array of messages arrays, keyed by channel identifiers
     *
     * @var array
     */
    public $chanMessages = array();

    /**
     * Array of subscriptions
     *
     * @var array
     */
    public $subscriptions = array();

    /**
     * Array of messages arrays, keyed by subscription identifiers
     *
     * @var array
     */
    public $subscriptionMessages = array();

    /**
     * Array of subscribers
     *
     * @var array
     */
    public $subscribers = array();

    /**
     * Message identifiers sequence
     *
     * @var int
     */
    private $messageIdSeq = 0;

    /**
     * Subscriptions identifiers sequence
     *
     * @var int
     */
    private $subscriptionIdSeq = 0;

    /**
     * Default constructor
     *
     * @param MemoryPubSub $backend Backend
     */
    public function __construct(MemoryPubSub $backend)
    {
        $this->backend = $backend;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::getBackend()
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::setOptions()
     */
    public function setOptions($options)
    {
        // FIXME: Parse options
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::getOptions()
     */
    public function getOptions()
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Get next message identifier
     *
     * @return int
     */
    public function getNextMessageIdentifier()
    {
        return ++$this->messageIdSeq;
    }

    /**
     * Get next message identifier
     *
     * @return int
     */
    public function getNextSubscriptionIdentifier()
    {
        return ++$this->subscriptionIdSeq;
    }
}
