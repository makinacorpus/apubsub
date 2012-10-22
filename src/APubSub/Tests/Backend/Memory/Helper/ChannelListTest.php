<?php

namespace APubSub\Tests\Backend\Memory\Helper;

use APubSub\Backend\Memory\MemoryPubSub;
use APubSub\Tests\Helper\AbstractChannelListTest;

class ChannelListTest extends AbstractChannelListTest
{
    protected function setUpBackend()
    {
        return new MemoryPubSub();
    }
}
