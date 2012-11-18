<?php

namespace ApbX\LocalCache;

use APubSub\MessageInterface;

interface MessageQueueInterface extends \Countable, \Traversable
{
    /**
     * No limit on maximum queue size
     */
    const NO_LIMIT = 0;

    /**
     * Sort by message creation time
     */
    const SORT_CREATE_TIME = 0x0001;

    /**
     * Sort by status unread first
     */
    const SORT_UNREAD_FIRST = 0x0002;

    /**
     * Sort ascending
     */
    const SORT_ASC = 1;

    /**
     * Sort descending
     */
    const SORT_DESC = 2;

    /**
     * Get maximum queue size.
     */
    public function getMaximumQueueSize();

    /**
     * Set maximum queue size
     *
     * Reseting the queue size will trigger items over limit purge per default
     *
     * @param int $limit    New maximum queue size
     * @param bool $doPurge Purge items over limit
     */
    public function setMaximumQueueSize($limit, $doPurge = true);

    /**
     * Has this instance been modified during runtime
     *
     * @return bool True if modified
     */
    public function isModified();

    /**
     * Set the modified state to false
     */
    public function resetModifiedState();

    /**
     * Force modified state to be true
     */
    public function setModified();

    /**
     * Prepend a message to the list
     *
     * @param MessageInterface $message Message
     */
    public function prepend(MessageInterface $message);

    /**
     * Prepend list of messages
     *
     * @param array|\Traversable $messages Message list
     */
    public function prependAll($messages);

    /**
     * Append a message to the list
     *
     * If the maximum queue size is reached the item won't be appended
     *
     * @param MessageInterface $message Message
     *
     * @return bool                     True if the operation succeeded false
     *                                  if the message has been dropped due to
     *                                  queue maximum size reached
     */
    public function append(MessageInterface $message);

    /**
     * Append list of messages
     *
     * @param array|\Traversable $messages Message list
     */
    public function appendAll($messages);

    /**
     * Remove a message from the list
     *
     * @param MessageInterface $message Message
     */
    public function remove(MessageInterface $message);

    /**
     * Enforce internal items to be dropped if internal queue size is reached
     *
     * @return boolean True if internal list has been modified
     */
    public function ensureLimit();

    /**
     * Sort internal list
     *
     * @param int $field Bitflag of sorting fields
     * @param int $order Sort order
     */
    public function sort(
        $field = MessageQueueInterface::SORT_CREATE_TIME,
        $order = MessageQueueInterface::SORT_DESC);

    /**
     * Get the unread item count
     *
     * @return int Unread item count
     */
    public function countUnread();

    /**
     * Tell if the queue contains unread items
     *
     * @return bool True if there is at least one unread item
     */
    public function hasUnread();

    /**
     * Tell if the queue is empty
     *
     * @return bool True if the queue is empty
     */
    public function isEmpty();
}
