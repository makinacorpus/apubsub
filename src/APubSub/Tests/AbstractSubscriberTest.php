<?php

namespace APubSub\Tests;

use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\Filter;

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

        $i = 10;
        $messages = $subscriber->fetch();
        foreach ($messages as $message) {
            $this->assertSame(--$i, $message->getContents());
        }

        $i = 0;
        $messages = $subscriber->fetch(Filter::NO_LIMIT, 0, null, Filter::FIELD_SENT, Filter::SORT_ASC);
        foreach ($messages as $message) {
            $this->assertSame(++$i, $message->getContents());
        }

        $i = 10;
        $messages = $subscriber->fetch(3, 0);
        foreach ($messages as $message) {
            $this->assertSame(--$i, $message->getContents());
        }
        $this->assertEquals(3, 10 - $i);

        $i = 7;
        $messages = $subscriber->fetch(3, 3);
        foreach ($messages as $message) {
          $this->assertSame(--$i, $message->getContents());
        }
        $this->assertEquals(3, 7 - $i);
    }
}
