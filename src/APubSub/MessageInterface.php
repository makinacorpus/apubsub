<?php

namespace APubSub;

use APubSub\Impl\ObjectInterface;

/**
 * Interface for all messages
 */
interface MessageInterface extends ObjectInterface
{
    /**
     * Get internal message identifier
     *
     * @return scalar Identifier whose type depends on the backend
     *                implementation
     */
    public function getId();

    /**
     * Get send UNIX timestamp
     *
     * @return int Send time UNIX timestamp
     */
    public function getSendTimestamp();

    /**
     * Get message contents
     *
     * @return mixed Data set by the sender
     */
    public function getContents();

    /**
     * Get the originating channel identifier
     *
     * Always prefer this getter than the getChannel() method, which will
     * attempt an implicit channel load
     *
     * @return string
     */
    public function getChannelId();

    /**
     * Get the originating channel
     *
     * @return \APubSub\ChannelInterface Channel that owns this message
     */
    public function getChannel();
}
