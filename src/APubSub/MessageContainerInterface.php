<?php

namespace APubSub;

/**
 * Describes a component that handles messages
 *
 * This interface does not include the fetch() and getMessage*() methods
 * because they are to behave differently over various contextes
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
}
