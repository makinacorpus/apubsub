<?php

namespace MakinaCorpus\APubSub\Notification\Registry;

use MakinaCorpus\APubSub\Notification\ChanType\NullChanType;

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
        return '\MakinaCorpus\APubSub\Notification\ChanType\DefaultChanType';
    }
}
