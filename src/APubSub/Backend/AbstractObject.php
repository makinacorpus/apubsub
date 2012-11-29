<?php

namespace APubSub\Backend;

use APubSub\ObjectInterface;

/**
 * Base implementation for objects suitable for most implementations
 */
abstract class AbstractObject implements ObjectInterface
{
    /**
     * @var \APubSub\ContextInterface
     */
    protected $context;

    /**
     * Get database connection
     *
     * @return \APubSub\ContextInterface Context
     */
    final public function getContext()
    {
        if (null === $this->context) {
            throw new \LogicException("Context is not set");
        }

        return $this->context;
    }
}
