<?php

namespace APubSub;

/**
 * Describes a component that handles messages
 */
interface MessageContainerInterface extends BackendAwareInterface
{
    /**
     * Fetch messages in message queue
     *
     * @param array $conditions  Array of key value pairs conditions, only the
     *                           "equal" operation is supported. If value is an
     *                           array, treat it as a "IN" operator
     *
     * @return CursorInterface   Iterable object of messages
     */
    public function fetch(array $conditions = null);

    /**
     * Delete all messages.
     */
    public function flush();
}
