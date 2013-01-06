<?php

namespace APubSub\Tests\Backend\Memory;

use APubSub\Backend\Memory\MemoryPubSub;
use APubSub\Tests\AbstractMessageTest;

class MessageTest extends AbstractMessageTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
