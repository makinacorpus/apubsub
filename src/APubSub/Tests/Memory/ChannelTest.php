<?php

namespace APubSub\Tests\Memory;

use APubSub\Memory\MemoryPubSub;
use APubSub\Tests\AbstractChannelTest;

class ChannelTest extends AbstractChannelTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
