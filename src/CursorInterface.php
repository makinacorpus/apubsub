<?php

namespace MakinaCorpus\APubSub;

/**
 * Cursor.
 *
 * Any object implementing this interface has one and only goal: list objects
 * handled by a specific backend.
 *
 * Objects can or cannot set a default limit, although they always should
 */
interface CursorInterface extends \Traversable, \Countable
{
    /**
     * No limit
     */
    const LIMIT_NONE = 0;

    /**
     * Sort order ascending
     */
    const SORT_ASC = 1;

    /**
     * Sort order descending
     */
    const SORT_DESC = -1;

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
     * @return CursorInterface Self reference for chaining
     */
    public function addSort($sort, $order = CursorInterface::SORT_ASC);

    /**
     * Set number of items to fetch
     *
     * @param int $limit                Limit
     *
     * @return CursorInterface Self reference for chaining
     */
    public function setLimit($limit);

    /**
     * Set starting offset
     *
     * @param int $offset               Offset
     *
     * @return CursorInterface Self reference for chaining
     */
    public function setOffset($offset);

    /**
     * Alias of bot setLimit() and setOffset()
     *
     * @param int $limit                Limit
     * @param int $offset               Offset
     *
     * @return CursorInterface Self reference for chaining
     */
    public function setRange($limit, $offset);

    /**
     * Get total number of items without taking into account the current limit
     *
     * @return int
     */
    public function getTotalCount();

    /**
     * Delete all selected items.
     *
     * Limit and offset apply on this method.
     */
    public function delete();

    /**
     * Update all selected items using given values.
     *
     * Limit and offset apply on this method.
     *
     * @param array $values Fields to update
     */
    public function update(array $values);
}
