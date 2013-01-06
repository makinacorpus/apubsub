<?php

namespace APubSub\Backend;

use APubSub\ObjectInterface;
use APubSub\ContextInterface;

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
     * Default constructor
     *
     * @param ContextInterface $context Context
     */
    public function __construct(ContextInterface $context = null)
    {
        if (null !== $context) {
            $this->context = $context;
        }
    }

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
