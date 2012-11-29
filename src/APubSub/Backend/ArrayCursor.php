<?php

namespace APubSub\Backend;

use APubSub\Backend\AbstractObject;
use APubSub\ContextInterface;
use APubSub\CursorInterface;

/**
 * Generic implementation of \APubSub\CursorInterface based upon data
 * originating from an array
 */
class ArrayCursor extends AbstractCursor implements
    \IteratorAggregate,
    CursorInterface
{
    /**
     * @var array
     */
    protected $array;

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param array $array              Data to iterate over
     */
    public function __construct(ContextInterface $context, array $array)
    {
        $this->array = $array;

        parent::__construct($context);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array();
    }

    /**
     * (non-PHPdoc)
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator()
    {
        return new \LimitIterator(
            new \ArrayIterator($this->array),
            $this->getOffset(), $this->getLimit());
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
