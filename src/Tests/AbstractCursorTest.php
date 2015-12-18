<?php

namespace MakinaCorpus\APubSub\Tests;

use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Field;

abstract class AbstractCursorTest extends AbstractBackendBasedTest
{
    /**
     * Basic channel cursor features
     *
     * Channels don't support update
     */
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
                CursorInterface::SORT_DESC
            )
        ;

        $this->assertCount(2, $cursor);
        $this->assertSame(6, $cursor->getTotalCount());

        $count = 0;
        foreach ($cursor as $chan) {
            $this->assertInstanceOf('\MakinaCorpus\APubSub\ChannelInterface', $chan);
            ++$count;
        }
        $this->assertSame(2, $count);

        $cursor->delete();

        $cursor = $this
            ->backend
            ->fetchChannels()
            ->setRange(2, 3)
            ->addSort(
                Field::CHAN_ID,
                CursorInterface::SORT_DESC
            )
        ;

        $this->assertSame(4, $cursor->getTotalCount());
        $this->assertCount(1, $cursor);
    }

    /**
     * Basic subscription cursor features
     *
     * Most update operations are done via the other tests (subscription basic
     * usage mostly)
     */
    public function testSubscriptionCursor()
    {
        $chanIdList = array(
            'foo',
            'bar',
        );
        foreach ($chanIdList as $chanId) {
            $this->backend->createChannel($chanId);
        }

        $sub1 = $this->backend->subscribe("foo");
        $sub2 = $this->backend->subscribe("bar");
        $sub3 = $this->backend->subscribe("foo");
        $sub4 = $this->backend->subscribe("foo");
        $sub5 = $this->backend->subscribe("bar");
        $sub6 = $this->backend->subscribe("foo");

        $cursor = $this
            ->backend
            ->fetchSubscriptions(array(
                Field::CHAN_ID => "foo",
            ))
            ->setLimit(3)
            ->addSort(
                Field::SUB_STATUS,
                CursorInterface::SORT_DESC
            )
        ;

        $this->assertCount(3, $cursor);
        $this->assertSame(4, $cursor->getTotalCount());

        $count = 0;
        foreach ($cursor as $sub) {
            $this->assertInstanceOf('\MakinaCorpus\APubSub\SubscriptionInterface', $sub);
            ++$count;
        }
        $this->assertSame(3, $count);

        $cursor->delete();

        $cursor = $this
            ->backend
            ->fetchSubscriptions()
            ->addSort(
                Field::SUB_STATUS,
                CursorInterface::SORT_DESC
            )
        ;

        $this->assertSame(3, $cursor->getTotalCount());
        $this->assertCount(3, $cursor);

        // Ensure there was no link on delete by loading the "bar" associated
        // subscriptions
        $cursor = $this
            ->backend
            ->fetchSubscriptions(array(
                Field::CHAN_ID => "bar",
            ))
            ->addSort(
                Field::SUB_STATUS,
                CursorInterface::SORT_DESC
            )
        ;

        $this->assertSame(2, $cursor->getTotalCount());
        $this->assertCount(2, $cursor);
    }
}
