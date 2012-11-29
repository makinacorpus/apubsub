<?php

namespace APubSub;

/**
 * Cursor.
 *
 * Any object implementing this interface has one and only goal: list objects
 * handled by a specific backend. Backends may not implement lists, since they
 * are an optional piece of the API which is meant to built admin UI
 *
 * Objects can or cannot set a default limit, although they always should
 *
 * The \Countable::count() interface method should return the total number of
 * items stored and not the limited given result set: this number can be
 * approximative and will be used for paging
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
     * For sort unread > read
     */
    const FIELD_MSG_UNREAD      = 22;

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
     * @param int $sort  Sort field
     * @param int $order Sort order for this field
     */
    public function addSort($sort, $order = CursorInterface::SORT_ASC);

    /**
     * Set number of items to fetch
     *
     * @param int $limit Limit
     */
    public function setLimit($limit);

    /**
     * Set starting offset
     *
     * @param int $offset Offset
     */
    public function setOffset($offset);

    /**
     * Alias of bot setLimit() and setOffset()
     *
     * @param int $limit  Limit
     * @param int $offset Offset
     */
    public function setRange($limit, $offset);
}
