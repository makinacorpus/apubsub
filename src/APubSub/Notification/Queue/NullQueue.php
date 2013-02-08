<?php

namespace APubSub\Notification\Queue;

use APubSub\Notification\QueueInterface;

class NullQueue implements QueueInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getType()
     */
    public function getType()
    {
        return 'null';
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getDescription()
     */
    public function getDescription()
    {
        return t("Null");
    }
}
