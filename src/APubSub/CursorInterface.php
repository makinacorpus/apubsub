<?php

namespace APubSub;

/**
 * Cursor.
 *
 * Any object implementing this interface has one and only goal: list objects
 * handled by a specific backend.
 *
 * Objects can or cannot set a default limit, although they always should
 */
interface CursorInterface extends ObjectInterface, \Traversable, \Countable
{
    /**
     * No limit
     */
    const LIMIT_NONE            = 0;

    /**
     * Sort order ascending
     */
    const SORT_ASC              = 1;

    /**
     * Sort order descending
     */
    const SORT_DESC             = -1;

    /**
     * Whatever object you are querying identifier
     */
    const FIELD_SELF_ID         = 1;

    /**
     * Channel id
     */
    const FIELD_CHAN_ID         = 10;

    /**
     * Message identifier
     */
    const FIELD_MSG_ID          = 20;

    /**
     * Message sent
     */
    const FIELD_MSG_SENT        = 21;

    /**
     * Message read/unread status
     *
     * When sorting, read < unread
     */
    const FIELD_MSG_UNREAD      = 22;

    /**
     * Message type
     */
    const FIELD_MSG_TYPE        = 23;

    /**
     * Read UNIX timestamp
     */
    const FIELD_MSG_READ_TS     = 24;

    /**
     * Message arbitrary level value
     */
    const FIELD_MSG_LEVEL       = 25;

    /**
     * Subscriber name
     */
    const FIELD_SUBER_NAME      = 30;

    /**
     * Subscription identifier
     */
    const FIELD_SUB_ID          = 31;

    /**
     * Subscription status
     *
     * For sort enabled > disabled
     */
    const FIELD_SUB_STATUS      = 32;

    /**
     * Return a list of available sort bit flags
     *
     * @return array Array of bitflags
     */
    public function getAvailableSorts();

    /**
     * Add a sort field
     *
     * @param int $sort                 Sort field
     * @param int $order                Sort order for this field
     *
     * @return \APubSub\CursorInterface Self reference for chaining
     */
    public function addSort($sort, $order = CursorInterface::SORT_ASC);

    /**
     * Set number of items to fetch
     *
     * @param int $limit                Limit
     *
     * @return \APubSub\CursorInterface Self reference for chaining
     */
    public function setLimit($limit);

    /**
     * Set starting offset
     *
     * @param int $offset               Offset
     *
     * @return \APubSub\CursorInterface Self reference for chaining
     */
    public function setOffset($offset);

    /**
     * Alias of bot setLimit() and setOffset()
     *
     * @param int $limit                Limit
     * @param int $offset               Offset
     *
     * @return \APubSub\CursorInterface Self reference for chaining
     */
    public function setRange($limit, $offset);

    /**
     * Get total number of items without taking into account the current limit
     *
     * @return int
     */
    public function getTotalCount();
}
