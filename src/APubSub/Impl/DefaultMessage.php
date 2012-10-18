<?php

namespace APubSub\Impl;

use APubSub\MessageInterface;
use APubSub\PubSubInterface;

/**
 * Default message implementation suitable for most backends
 */
class DefaultMessage implements MessageInterface
{
    /**
     * Message identifier
     *
     * @var scalar
     */
    protected $id;

    /**
     * Send time UNIX timestamp
     *
     * @var int
     */
    protected $sendTime;

    /**
     * Message raw data
     *
     * @var mixed
     */
    protected $contents;

    /**
     * Channel identifier
     *
     * @var string
     */
    protected $chanId;

    /**
     * @var \APubSub\Impl\ContextInterface
     */
    protected $context;

    /**
     * (non-PHPdoc)
     * @see \APubSub\Impl\ObjectInterface::getContext()
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param string $chanId            Channel identifier
     * @param mixed $contents           Message contents
     * @param scalar $id                Message identifier
     * @param int $sendTime             Send time UNIX timestamp
     */
    public function __construct(ContextInterface $context,
        $chanId, $contents, $id, $sendTime)
    {
        $this->id = $id;
        $this->chanId = $chanId;
        $this->contents = $contents;
        $this->sendTime = $sendTime;
        $this->context = $context;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSendTimestamp()
     */
    public function getSendTimestamp()
    {
        return $this->sendTime;
    }

    /**
     * Set sent timestamp
     *
     * @param int $sendTime UNIX timestamp when the message is being sent
     */
    public function setSendTimestamp($sendTime)
    {
        $this->sendTime = $sendTime;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getContents()
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getChannelId()
     */
    public function getChannelId()
    {
        return $this->chanId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getChannel()
     */
    public function getChannel()
    {
        return $this->context->getBackend()->getChannel($this->chanId);
    }
}
