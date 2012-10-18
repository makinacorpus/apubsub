<?php

namespace APubSub\Drupal7;

use APubSub\Impl\ObjectInterface;

/**
 * Base implementation for Drupal 7 objects
 */
abstract class AbstractD7Object implements ObjectInterface
{
    /**
     * @var \APubSub\Drupal7\D7Context
     */
    protected $context;

    /**
     * Set database connection
     *
     * @param D7Context $context Context
     */
    public function setContext(D7Context $context)
    {
        if (null !== $this->context) {
            throw new \LogicException("Context cannot be unset");
        }

        $this->context = $context;
    }

    /**
     * Get database connection
     *
     * @return \APubSub\Drupal7\D7Context Context
    */
    public function getContext()
    {
        if (null === $this->context) {
            throw new \LogicException("Context is not set");
        }

        return $this->context;
    }
}
