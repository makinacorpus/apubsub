<?php

namespace APubSub\Memory;

/**
 * Base implementation for all memory objects.
 */
abstract class AbstractMemoryObject
{
    /**
     * @var \APubSub\Memory\MemoryContext
     */
    protected $context;

    /**
     * Set database connection
     *
     * @param MemoryContext $context Context
     */
    public function setContext(MemoryContext $context)
    {
        if (null !== $this->context) {
            throw new \LogicException("Context cannot be unset");
        }

        $this->context = $context;
    }

    /**
     * Get database connection
     *
     * @return \APubSub\Memory\MemoryContext Context
    */
    public function getContext()
    {
        if (null === $this->context) {
            throw new \LogicException("Context is not set");
        }

        return $this->context;
    }
}
