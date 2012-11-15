<?php

namespace APubSub\Backend\Memory\Helper;

use APubSub\Helper\ListInterface;

/**
 * Generic implementation of \APubSub\Helper\ListInterface based upon data
 * originating from an array
 */
class ArrayList implements \IteratorAggregate, ListInterface
{
    /**
     * @var array
     */
    protected $array;

    /**
     * @var int
     */
    protected $limit = 20;

    /**
     * @var int
     */
    protected $offset = 0;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::addSort()
     */
    public function addSort(
        $sort = ListInterface::SORT_FIELD_ID,
        $order = ListInterface::SORT_ORDER_ASC)
    {
        throw new \LogicException("Sorting is not possible with this object");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::setLimit()
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::setOffset()
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * (non-PHPdoc)
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator()
    {
        return new \LimitIterator(
            new \ArrayIterator($this->array),
            $this->offset, $this->limit);
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    public function count()
    {
        return count($this->array);
    }
}
