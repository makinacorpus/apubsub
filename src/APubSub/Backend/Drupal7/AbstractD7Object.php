<?php

namespace APubSub\Backend\Drupal7;

use APubSub\ObjectInterface;

/**
 * Base implementation for Drupal 7 objects
 */
abstract class AbstractD7Object implements ObjectInterface
{
    /**
     * @var \APubSub\Backend\Drupal7\D7Context
     */
    protected $context;

    /**
     * Get database connection
     *
     * @return \APubSub\Backend\Drupal7\D7Context Context
    */
    public function getContext()
    {
        if (null === $this->context) {
            throw new \LogicException("Context is not set");
        }

        return $this->context;
    }
}
