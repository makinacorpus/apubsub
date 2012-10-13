<?php

namespace APubSub\Tests\Memory;

use APubSub\Memory\MemoryPubSub;
use APubSub\Tests\AbstractSubscriberTest;

class SubscriberTest extends AbstractSubscriberTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
