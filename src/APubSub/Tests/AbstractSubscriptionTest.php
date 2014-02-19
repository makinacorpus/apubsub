<?php

namespace APubSub\Tests;

use APubSub\CursorInterface;
use APubSub\Field;

abstract class AbstractSubscriptionTest extends AbstractBackendBasedTest
{
    /**
     * @var \APubSub\ChannelInterface
     */
    protected $chan;

    protected function setUp()
    {
        parent::setUp();
        $this->chan = $this->backend->createChannel('foo');
    }

    public function testSubscribe()
    {
        $subscription = $this->chan->subscribe();
        $this->assertInstanceOf('\APubSub\SubscriptionInterface', $subscription);

        $id = $subscription->getId();
        $this->assertFalse(empty($id));

        $chan = $subscription->getChannel();
        // Depending on the implementation, the instance might not be the same
        // so only check for ids to be the same
        $this->assertSame($chan->getId(), $this->chan->getId());

        // Per definition a new subscription is always inactive
        $this->assertFalse($subscription->isActive());

        try {
            $subscription->getStartTime();
            $this->fail("Subscriber should not have a start time");
        } catch (\Exception $e) {
        }

        // Should not raise any exception
        $subscription->getStopTime();

        $loaded = $this->backend->getSubscription($subscription->getId());
        $this->assertSame(get_class($subscription), get_class($loaded));
        $this->assertSame($subscription->getId(), $loaded->getId());
        $this->assertFalse($loaded->isActive());

        $this->assertSame($this->chan->getId(), $subscription->getChannel()->getId());
        $this->assertSame($this->chan->getId(), $loaded->getChannel()->getId());

        $subscription->activate();

        try {
            $subscription->getStartTime();
        } catch (\Exception $e) {
            $this->fail("Subscriber should have a start time");
        }
    }

    public function testFetch()
    {
        $subscription = $this->chan->subscribe();
        $chan         = $subscription->getChannel();

        $chan->send(42);

        $messages = $subscription->fetch();
        foreach ($messages as $message) {
            $this->fail("This cursor should be empty");
        }

        $subscription->activate();
        $chan->send(24);
        $messages = $subscription->fetch();
        $this->assertNotEmpty($messages);
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        $this->assertCount(1, $messages);
        foreach ($messages as $message) {
            // There is only one item in this queue
            $this->assertSame(24, $message->getContents());
        }

        // Ensures that message have been kept
        $messages = $subscription->fetch();
        $this->assertNotEmpty($messages);
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        $this->assertCount(1, $messages);

        // Ensures we still have only one message after send
        $subscription->deactivate();
        $chan->send(12);
        $messages = $subscription->fetch();
        $this->assertNotEmpty($messages);
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        $this->assertCount(1, $messages);
    }

    function testMultipleFetch()
    {
        $sub1 = $this->chan->subscribe();
        $sub1->activate();
        $sub2 = $this->chan->subscribe();
        $msg1 = $this->chan->send(1);
        $sub2->activate();
        $msg2 = $this->chan->send(2);
        $sub3 = $this->chan->subscribe();
        $sub3->activate();
        $msg3 = $this->chan->send(3);

        $messages = $sub1->fetch();
        $this->assertCount(3, $messages, "Sub 1 message count is 3");

        $messages = $sub2->fetch();
        $this->assertCount(2, $messages, "Sub 2 message count is 3");

        // We cannot assert that messages 1 and 2 does not exist anymore since
        // the delete behavior is not documented at the interface level, we
        // don't care about that anyway, at least ensure that non fully dequeued
        // messages are still here
        $messages = $sub2->fetch(array(
            Field::MSG_ID => $msg3->getId(),
        ));

        $messages = $sub3->fetch();
        $this->assertCount(1, $messages, "Sub 3 message count is 1");
    }

    public function testFetchCursorOrder()
    {
        $subscriber = $this->backend->getSubscriber('baz');
        $chan1      = $this->chan;
        $chan2      = $this->backend->createChannel('bar');
        $sub1       = $subscriber->subscribe('foo');
        $sub2       = $subscriber->subscribe('bar');

        $chan1->send(1);
        $chan2->send(2);
        $chan1->send(3);
        $chan2->send(4);
        $chan1->send(5);
        $chan2->send(6);

        $i = 0;
        $cursor = $sub2->fetch();
        foreach ($cursor as $message) {
            $i += 2;
            $this->assertSame($i, $message->getContents());
        }
        $this->assertEquals($i, 3 * 2);

        $i = 8;
        $cursor = $sub2->fetch();
        $cursor->setLimit(CursorInterface::LIMIT_NONE);
        $cursor->addSort(Field::MSG_SENT, CursorInterface::SORT_DESC);
        foreach ($cursor as $message) {
            $i -= 2;
            $this->assertSame($i, $message->getContents());
        }
    }

    public function testExclusion()
    {
        $suber1 = $this->backend->getSubscriber('john');
        $suber2 = $this->backend->getSubscriber('doe');
        $suber3 = $this->backend->getSubscriber('jane');
        $suber4 = $this->backend->getSubscriber('smith');

        $chan1      = $this->chan;
        $sub1       = $suber1->subscribe($chan1->getId());
        $sub2       = $suber2->subscribe($chan1->getId());
        $sub3       = $suber3->subscribe($chan1->getId());
        $sub4       = $suber4->subscribe($chan1->getId());

        $chan1->send("test", 'foo', 0, array(
            $sub2->getId(),
            $sub4->getId(),
        ));

        $this->assertCount(1, $suber1->fetch());
        $this->assertCount(0, $suber2->fetch());
        $this->assertCount(1, $suber3->fetch());
        $this->assertCount(0, $suber4->fetch());
    }

    public function testMassUpdate()
    {
        $subscriber = $this->backend->getSubscriber('baz');
        $chan1      = $this->chan;
        $sub1       = $subscriber->subscribe($chan1->getId());

        $chan1->send(1);
        $chan1->send(2);
        $chan1->send(3);
        $chan1->send(4);
        $chan1->send(5);
        $chan1->send(6);

        $sub1
            ->fetch(array(
                Field::MSG_ID => array(
                    1,
                    3,
                    5,
                ),
            ))
            ->update(array(
                Field::MSG_UNREAD => false,
            ));

        $cursor = $sub1->fetch();
        foreach ($cursor as $message) {
            $id = $message->getId();

            if ($id % 2) { // 1, 2, 5
                $this->assertFalse($message->isUnread());
            } else { // 2, 4, 6
                $this->assertTrue($message->isUnread());
            }
        }
    }
}
