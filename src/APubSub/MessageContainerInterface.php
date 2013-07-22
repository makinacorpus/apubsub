<?php

namespace APubSub;

/**
 * Describes a component that handles messages
 */
interface MessageContainerInterface extends ObjectInterface
{
    /**
     * Delete a single message and all its references in all existing
     * subscriptions queues
     *
     * Method is silent if message does not exists in the current context
     *
     * @param scalar $id Message identifier
     */
    public function deleteMessage($id);

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
    public function deleteMessages(array $conditions = null);

    /**
     * Delete all messages in this queue
     *
     * Alias of deleteMessages() with no conditions
     */
    public function deleteAllMessages();

    /**
     * Get a single message
     *
     * @param scalar $id                 Message identifier
     *
     * @return \APubSub\MessageInterface Loaded message
     *
     * @throws \APubSub\Error\MessageDoesNotExistException
     *                                   If message does not exist in the
     *                                   current container
     */
    public function getMessage($id);

    /**
     * Get a list of messages
     *
     * @param array $idList                List of message identifiers
     *
     * @return \APubSub\MessageInterface[] Loaded messages array
     *
     * @throws \APubSub\Error\MessageDoesNotExistException
     *                                     If one of more messages don't exist
     *                                     in the current container
     */
    public function getMessages(array $idList);

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
     * Alias of deleteAllMessages()
     */
    public function flush();
}
