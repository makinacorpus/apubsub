<?php

namespace APubSub\Impl;

/**
 * Base interface for all objects of this API.
 *
 * Setter is not part of the public API and should never be exposed to users
 */
interface ObjectInterface
{
    /**
     * Get object context
     *
     * @return \APubSub\Impl\ContextInterface
     */
    public function getContext();
}
