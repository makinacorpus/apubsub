<?php

namespace APubSub\Tests\Backend\Memory;

use APubSub\Backend\Memory\MemoryPubSub;
use APubSub\Tests\AbstractChannelListTest;

class ChannelListTest extends AbstractChannelListTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
