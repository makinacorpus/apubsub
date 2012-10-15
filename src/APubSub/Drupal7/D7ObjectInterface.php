<?php

namespace APubSub\Drupal7;

/**
 * Common interface for all Drupal 7 based objects
 */
interface D7ObjectInterface
{
    /**
     * Set context
     *
     * @param D7Context $context Context
     */
    public function setContext(D7Context $context);

    /**
     * Get context
     *
     * @return D7Context Context
     */
    public function getContext();
}
