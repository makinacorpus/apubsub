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
        $contents, $id = null, $sendTime = null)
    {
        if (null !== $id) {
            $this->id = $id;
        }
        if (null !== $sendTime) {
            $this->sendTime = $sendTime;
        }

        $this->contents = $contents;
        $this->channel = $channel;
    }

    /**
     * Set identifier
     *
     * @param scalar $id
     */
    public function setId($id)
    {
        if (null !== $this->id) {
            throw new \LogicException("Message already has an identifier");
        }

        $this->id = $id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getId()
     */
    public function getId()
    {
        if (null === $this->id) {
            throw new \LogicException("Message has not been sent yet");
        }

        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::getSendTimestamp()
     */
    public function getSendTimestamp()
    {
        if (null === $this->id) {
            throw new \LogicException("Message has not been sent yet");
        }

        return $this->sendTime;
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
