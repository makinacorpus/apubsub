<?php

namespace APubSub;

/**
 * Defines a single subscription
 */
interface SubscriptionInterface extends ObjectInterface, ChannelAwareInterface
{
    /**
     * Get subscriber identifier
     *
     * @return scalar Subsriber identifier Can be any scalar type, depending
     *                                     on how the channel handles its
     *                                     subscribers
     */
    public function getId();

    /**
     * Get creation time as a UNIX timestamp
     *
     * @return int UNIX timestamp where the channel was created
     */
    public function getCreationTime();

    /**
     * Does this subscription is still active
     *
     * @return bool Active state
     */
    public function isActive();

    /**
     * Get the UNIX timestamp when this subscription started
     *
     * @return int Unix timestamp
     */
    public function getStartTime();

    /**
     * Get the UNIX timestamp when this subscription stopped
     *
     * @return int               Unix timestamp
     *
     * @throws \RuntimeException If subscription is still active
     */
    public function getStopTime();

    /**
     * Delete all messages linked to that subscription and the subscription
     * itself. Once this call, you can get rid of the instance you have because
     * it doesn't exist anymore
     */
    public function delete();

    /**
     * Fetch current message queue
     *
     * @param number $limit      Message count to fetch
     * @param number $offset     Start offset
     * @param array $conditions  Array of key value pairs conditions, only the
     *                           = operator is supported right now
     * @param string $sortField  Sort field: all CursorInterface::FIELD_*
     *                           constants will be supported by all backends
     * @param int $sortDirection Sort direction
     *
     * @return array            Array of messages
     */
    public function fetch(
        $limit            = CursorInterface::LIMIT_NONE,
        $offset           = 0,
        array $conditions = null,
        $sortField        = CursorInterface::FIELD_MSG_SENT,
        $sortDirection    = CursorInterface::SORT_DESC);

    /**
     * Deactivate this subscription, if it is already deactivated it will
     * remain silent
     */
    public function deactivate();

    /**
     * Activate this subscription, if it is already activate it will remain
     * silent
     */
    public function activate();

    /**
     * Delete everyting in this subscription queue
     */
    public function flush();

    /**
     * Set the unread status of a specific message
     *
     * Method is silent if message does not exist in this subscription queue
     *
     * @param scalar $messageId Message identifier
     * @param bool $toggle      True for unread, false for read
     */
    public function setUnread($messageId, $toggle = false);
}
