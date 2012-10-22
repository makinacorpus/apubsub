<?php

namespace APubSub\Tests\Helper;

use APubSub\Tests\AbstractBackendBasedTest;

abstract class AbstractChannelListTest extends AbstractBackendBasedTest
{
    public function testPaging()
    {
        $this->backend->createChannels(range(1, 10));

        $page1List = $this->backend->getChannelListHelper();
        $page1List->setLimit(3);
        $page1List->setOffset(0);
        $page1 = iterator_to_array($page1List);
        $this->assertCount(3, $page1);

        $page2List = $this->backend->getChannelListHelper();
        $page2List->setLimit(3);
        $page2List->setOffset(3);
        $page2 = iterator_to_array($page2List);
        $this->assertCount(3, $page2);

        $page4List = $this->backend->getChannelListHelper();
        $page4List->setLimit(3);
        $page4List->setOffset(9);
        $page4 = iterator_to_array($page4List);
        $this->assertCount(1, $page4);
    }
}
