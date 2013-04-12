<?php

namespace APubSub;

/**
 * Describes a component that handles messages
 */
interface MessageContainerInterface
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
     * @param array $idList List of message identifiers
     */
    public function deleteMessages(array $idList);

    /**
     * Delete all messages in this queue
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
     * Alias of deleteAllMessages()
     */
    public function flush();
}
