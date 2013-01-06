<?php

namespace APubSub;

/**
 * The object interface ties the context interface to this API business objects
 * and normalize some common technical accessors
 *
 * It was originally meant to be an internal only class for factorizing options
 * and database connection into the Drupal 7 backend, it evolved to be a centric
 * point for accessing technical information about the backend. For implementors
 * that don't internally need it, just return a mock object that implements the
 * ContextInterface and everyone will be happy
 *
 * Setter is not part of the public API and should never be exposed to users
 */
interface ObjectInterface
{
    /**
     * Get object context
     *
     * @return \APubSub\ContextInterface
     */
    public function getContext();
}
