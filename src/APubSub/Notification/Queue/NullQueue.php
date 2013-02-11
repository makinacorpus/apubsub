<?php

namespace APubSub\Notification\Queue;

use APubSub\CursorInterface;
use APubSub\Notification\QueueInterface;

class NullQueue implements QueueInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getType()
     */
    public function getType()
    {
        return 'null';
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getDescription()
     */
    public function getDescription()
    {
        return t("Null");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getGroupId()
     */
    public function getGroupId()
    {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\QueueInterface::process()
     */
    public function process(CursorInterface $cursor)
    {
        return true;
    }
}
