<?php

namespace APubSub;

use APubSub\ObjectInterface;

/**
 * Interface for all messages
 */
interface MessageInterface extends ObjectInterface, ChannelAwareInterface
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
}
