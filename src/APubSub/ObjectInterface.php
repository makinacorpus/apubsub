<?php

namespace APubSub;

/**
 * Any object of this API is tied to a context
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
