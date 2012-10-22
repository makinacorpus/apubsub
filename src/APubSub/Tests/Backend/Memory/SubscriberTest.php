<?php

namespace APubSub\Tests\Backend\Memory;

use APubSub\Backend\Memory\MemoryPubSub;
use APubSub\Tests\AbstractSubscriberTest;

class SubscriberTest extends AbstractSubscriberTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
