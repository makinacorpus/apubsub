<?php

namespace APubSub;

/**
 * Describes a component that handles messages
 */
interface MessageContainerInterface extends ObjectInterface
{
    /**
     * Delete a set of messages and all their references in all existing
     * subscriptions queues
     *
     * Method is silent if one or more messages do not exists in the current
     * context
     *
     * @param array $conditions  Array of key value pairs conditions, only the
     *                           "equal" operation is supported. If value is an
     *                           array, treat it as a "IN" operator
     */
    public function delete(array $conditions = null);

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
     * Mass update in message queue
     *
     * Warning: this might be an synchronous operation depending on the backend
     *
     * @param array $values      Array of values to change, keys are field names
     *                           and values the value to set
     * @param array $conditions  Array of key value pairs conditions, only the
     *                           "equal" operation is supported. If value is an
     *                           array, treat it as a "IN" operator
     */
    public function update(array $values, array $conditions = null);

    /**
     * Delete all messages.
     */
    public function flush();
}
