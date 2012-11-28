<?php

namespace APubSub\Tests;

use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;

abstract class AbstractSubscriberTest extends AbstractBackendBasedTest
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

    public function testGetSubscriber()
    {
        $subscriber = $this->backend->getSubscriber('foo');
        $this->assertInstanceOf('\APubSub\SubscriberInterface', $subscriber);

        $subscriber = $this->backend->getSubscriber('bar');
        $this->assertInstanceOf('\APubSub\SubscriberInterface', $subscriber);
    }

    public function testSubscribe()
    {
        $subscriber = $this->backend->getSubscriber('foo');

        try {
            $subscriber->getSubscriptionFor('foo');

            $this->fail("Should have thrown a SubscriptionDoesNotExistException exception");
        } catch (SubscriptionDoesNotExistException $e) {
            $this->assertTrue(true, "Subscription does not exists");
        }

        $subscription = $subscriber->subscribe('foo');
        $this->assertInstanceOf('\APubSub\SubscriptionInterface', $subscription);

        try {
            $subscriber->subscribe('foo');

            $this->fail("Should have thrown a SubscriptionAlreadyExistsException exception");
        } catch (SubscriptionAlreadyExistsException $e) {
            $this->assertTrue(true, "Subscription already exists");
        }

        try {
            $subscriber->subscribe('bar');

            $this->fail("Should have thrown a ChannelDoesNotExistException exception");
        } catch (ChannelDoesNotExistException $e) {
            $this->assertTrue(true, "Channel does not exists");
        }
    }

    public function testGetters()
    {
        $subscriber = $this->backend->getSubscriber('foo');
        $subscription = $subscriber->subscribe('foo');
        $id = $subscription->getId();

        $channel = $subscription->getChannel();
        $this->assertSame($channel->getId(), $this->channel->getId());

        $subscription = $subscriber->getSubscriptionFor('foo');
        $this->assertSame($subscription->getId(), $id);

        $newChannel = $this->backend->createChannel('bar');
        $newSubscription = $subscriber->subscribe($newChannel->getId());

        $subscriptions = $subscriber->getSubscriptions();
        $this->assertCount(2, $subscriptions);
        // FIXME: Test subscriptions identifiers? Order is not respected
    }

    public function testFetchOrder()
    {
        $subscriber = $this->backend->getSubscriber('baz');

        $chan1 = $this->channel;
        $chan2 = $this->backend->createChannel('bar');
        $chan3 = $this->backend->createChannel('baz');

        $sub1 = $subscriber->subscribe('foo');
        $sub2 = $subscriber->subscribe('bar');
        $sub3 = $subscriber->subscribe('baz');

        $chan1->send(1);
        $chan2->send(2);
        $chan3->send(3);
        $chan2->send(4);
        $chan3->send(5);
        $chan1->send(6);
        $chan3->send(7);
        $chan2->send(8);
        $chan1->send(9);

        $i = 0;
        foreach ($subscriber->fetch() as $message) {
            $this->assertSame(++$i, $message->getContents());
        }
    }

    public function testFetchHead()
    {
        
    }

    public function testFetchTail()
    {
        
    }
}
