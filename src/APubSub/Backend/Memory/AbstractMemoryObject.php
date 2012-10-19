<?php

namespace APubSub\Backend\Memory;

/**
 * Base implementation for all memory objects
 */
abstract class AbstractMemoryObject
{
    /**
     * @var \APubSub\Backend\Memory\MemoryContext
     */
    protected $context;

    /**
     * Get database connection
     *
     * @return \APubSub\Backend\Memory\MemoryContext Context
    */
    public function getContext()
    {
        if (null === $this->context) {
            throw new \LogicException("Context is not set");
        }

        return $this->context;
    }
}
