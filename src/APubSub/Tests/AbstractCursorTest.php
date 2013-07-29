<?php

namespace APubSub\Tests;

use APubSub\CursorInterface;
use APubSub\Field;

abstract class AbstractCursorTest extends AbstractBackendBasedTest
{
    public function testChannelCursor()
    {
        $chanIdList = array(
            'a',
            'b',
            'c',
            'foo',
            'bar',
            'baz',
        );

        foreach ($chanIdList as $chanId) {
            $this->backend->createChannel($chanId);
        }

        $cursor = $this
            ->backend
            ->fetchChannels()
            ->setRange(2, 3)
            ->addSort(
                Field::CHAN_ID,
                CursorInterface::SORT_DESC);

        $this->assertCount(2, $cursor);
        $this->assertSame(6, $cursor->getTotalCount());

        $count = 0;
        foreach ($cursor as $chan) {
            $this->assertInstanceOf('\APubSub\ChannelInterface', $chan);
            ++$count;
        }
        $this->assertSame(2, $count);
    }
}
