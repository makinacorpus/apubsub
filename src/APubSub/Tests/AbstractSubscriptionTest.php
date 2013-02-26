<?php

namespace APubSub\Tests;

use APubSub\CursorInterface;

abstract class AbstractSubscriptionTest extends AbstractBackendBasedTest
{
    /**
     * @var \APubSub\ChannelInterface
     */
    protected $channel;

    protected function setUp()
    {
        parent::setUp();
        $this->channel = $this->backend->createChannel('foo');
    }

    public function testSubscribe()
    {
        $subscription = $this->channel->subscribe();
        $this->assertInstanceOf('\APubSub\SubscriptionInterface', $subscription);

        $id = $subscription->getId();
        $this->assertFalse(empty($id));

        $channel = $subscription->getChannel();
        // Depending on the implementation, the instance might not be the same
        // so only check for ids to be the same
        $this->assertSame($channel->getId(), $this->channel->getId());

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

        $this->assertSame($this->channel->getId(), $subscription->getChannel()->getId());
        $this->assertSame($this->channel->getId(), $loaded->getChannel()->getId());

        $subscription->activate();

        try {
            $subscription->getStartTime();
        } catch (\Exception $e) {
            $this->fail("Subscriber should have a start time");
        }
    }

    public function testExtraData()
    {
        $subscription = $this->channel->subscribe();

        $subscription->setExtraData(array('test' => 42));

        $data = $subscription->getExtraData();
        $this->assertSame(42, $data['test']);
    }

    public function testFetch()
    {
        $subscription = $this->channel->subscribe();
        $channel      = $subscription->getChannel();

        $channel->send(42);

        $messages = $subscription->fetch();
        foreach ($messages as $message) {
            $this->fail("This cursor should be empty");
        }

        $subscription->activate();
        $channel->send(24);
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
        $channel->send(12);
        $messages = $subscription->fetch();
        $this->assertNotEmpty($messages);
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        $this->assertCount(1, $messages);
    }

    function testMultipleFetch()
    {
        $sub1 = $this->channel->subscribe();
        $sub1->activate();
        $sub2 = $this->channel->subscribe();
        $msg1 = $this->channel->send(1);
        $sub2->activate();
        $msg2 = $this->channel->send(2);
        $sub3 = $this->channel->subscribe();
        $sub3->activate();
        $msg3 = $this->channel->send(3);

        $messages = $sub1->fetch();
        $this->assertCount(3, $messages, "Sub 1 message count is 3");

        $messages = $sub2->fetch();
        $this->assertCount(2, $messages, "Sub 2 message count is 3");

        // We cannot assert that messages 1 and 2 does not exist anymore since
        // the delete behavior is not documented at the interface level, we
        // don't care about that anyway, at least ensure that non fully dequeued
        // messages are still here
        $messages = $this->channel->getMessage($msg3->getId());

        $messages = $sub3->fetch();
        $this->assertCount(1, $messages, "Sub 3 message count is 1");
    }

    public function testFetchCursorOrder()
    {
        $subscriber = $this->backend->getSubscriber('baz');
        $chan1      = $this->channel;
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
        $cursor->addSort(CursorInterface::FIELD_MSG_SENT, CursorInterface::SORT_DESC);
        foreach ($cursor as $message) {
            $i -= 2;
            $this->assertSame($i, $message->getContents());
        }
    }

    public function testMassUpdate()
    {
        $subscriber = $this->backend->getSubscriber('baz');
        $chan1      = $this->channel;
        $sub1       = $subscriber->subscribe($chan1->getId());

        $chan1->send(1);
        $chan1->send(2);
        $chan1->send(3);
        $chan1->send(4);
        $chan1->send(5);
        $chan1->send(6);

        $sub1->update(
            array(
                CursorInterface::FIELD_MSG_UNREAD => false,
            ),
            array(
                CursorInterface::FIELD_MSG_ID => array(
                    1,
                    3,
                    5,
                ),
            ));

        $cursor = $sub1->fetch();
        foreach ($cursor as $message) {
            $id = $message->getId();

            if ($id % 2) { // 1, 2, 5
                $this->assertFalse($message->isUnread());
            } else { // 2, 4, 6
                //$this->assertTrue($message->isUnread());
            }
        }
    }
}
