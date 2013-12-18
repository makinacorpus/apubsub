<?php

namespace APubSub\Notification\Registry;

use APubSub\Notification\ChanType\NullChanType;

/**
 * Channel type registry
 */
class ChanTypeRegistry extends AbstractRegistry
{
    protected function createNullInstance()
    {
        return new NullChanType();
    }

    protected function getDefaultClass()
    {
        return '\APubSub\Notification\ChanType\DefaultChanType';
    }
}
