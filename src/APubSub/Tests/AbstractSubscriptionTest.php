<?php

namespace APubSub\Tests;

abstract class AbstractSubscriptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \APubSub\PubSubInterface
     */
    protected $backend;

    /**
     * @var \APubSub\ChannelInterface
     */
    protected $channel;

    /**
     * Create the backend for testing
     *
     * @return \APubSub\PubSubInterface Ready to use mock instance
     */
    abstract protected function setUpBackend();

    protected function setUp()
    {
        $this->backend = $this->setUpBackend();
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

    public function testFetch()
    {
        $subscription = $this->channel->subscribe();
        $channel      = $subscription->getChannel();

        $channel->send(42);

        $messages = $subscription->fetch();
        $this->assertEmpty($messages);

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

        $messages = $subscription->fetch();
        $this->assertEmpty($messages);
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        $this->assertCount(0, $messages);

        $subscription->deactivate();
        $channel->send(12);
        $messages = $subscription->fetch();
        $this->assertEmpty($messages);
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        $this->assertCount(0, $messages);
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
}
