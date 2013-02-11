<?php

namespace APubSub\Notification\Registry;

use APubSub\Notification\ChanType\NullChanType;

/**
 * Channel type registry
 */
class ChanTypeRegistry extends AbstractRegistry
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\Registry\AbstractRegistry::createNullInstance()
     */
    protected function createNullInstance()
    {
        return new NullChanType();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\Registry\AbstractRegistry::getDefaultClass()
     */
    protected function getDefaultClass()
    {
        return '\APubSub\Notification\ChanType\DefaultChanType';
    }
}
