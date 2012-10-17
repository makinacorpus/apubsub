<?php

namespace APubSub\Impl;

use APubSub\ChannelInterface;
use APubSub\MessageInterface;

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
     * Channel this message belongs to
     *
     * @var \APubSub\ChannelInterface
     */
    protected $channel;

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
     * Default constructor
     *
     * @param ChannelInterface $channel Channel this message belongs to
     * @param mixed $contents           Message contents
     * @param scalar $id                Message identifier
     * @param int $sendTime             Send time UNIX timestamp
     */
    public function __construct(ChannelInterface $channel,
        $contents, $id, $sendTime)
    {
        $this->id = $id;
        $this->contents = $contents;
        $this->channel = $channel;
        $this->sendTime = $sendTime;
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
     * @see \APubSub\MessageInterface::getChannel()
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
