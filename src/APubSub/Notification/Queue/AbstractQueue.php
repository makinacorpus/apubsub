<?php

namespace APubSub\Notification\Queue;

use APubSub\Notification\QueueInterface;
use APubSub\Notification\Registry\AbstractRegistryItem;

abstract class AbstractQueue extends AbstractRegistryItem implements
    QueueInterface
{
}
