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
     * Sort order ascending
     */
    const SORT_ASC            = 1;

    /**
     * Sort order descending
     */
    const SORT_DESC           = -1;

    /**
     * Sort field id
     */
    const FIELD_ID            = 0x0001;

    /**
     * Sort field name
     */
    const FIELD_NAME          = 0x0002;

    /**
     * Sort field created UNIX timestamp
     */
    const FIELD_CREATED       = 0x0004;

    /**
     * Sort field subscription status (activated or deactivated)
     */
    const FIELD_SUB_STATUS    = 0x0008;

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
    public function addSort(
        $sort  = CursorInterface::FIELD_ID,
        $order = CursorInterface::SORT_ASC);

    /**
     * Set number of items to fetch
     *
     * @param int $limit
     */
    public function setLimit($limit);

    /**
     * Set starting offset
     *
     * @param int $offset
     */
    public function setOffset($offset);
}
