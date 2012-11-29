<?php

namespace APubSub\Backend;

class ApplyIteratorIterator extends \IteratorIterator
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * Default constructor
     *
     * @param \Traversable $iterator Data to iterate onto
     * @param callable $callback     Callback to run on items
     */
    public function __construct(\Traversable $iterator, $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException("Callback must be callable");
        }

        parent::__construct($iterator);
    }

    /**
     * (non-PHPdoc)
     * @see \ArrayIterator::next()
     */
    public function next()
    {
        if ($ret = parent::next()) {
            return call_user_func($this->callback, $ret);
        } else {
            // Result is either null or false
            return $ret;
        }
    }
}
