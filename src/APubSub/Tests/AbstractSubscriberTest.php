<?php

namespace APubSub\Tests;

use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionAlreadyExistsException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\CursorInterface;

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
        } catch (SubscriptionAlreadyExistsException $e) {
            $this->assertFalse(true, "This should have been silent");
        }

        try {
            $subscriber->subscribe('bar');

            $this->fail("Should have thrown a ChannelDoesNotExistException exception");
        } catch (ChannelDoesNotExistException $e) {
            $this->assertTrue(true, "Channel does not exists");
        }

        // This should not throw any exception
        $subscriber->unsubscribe('foo');
        $subscriber->unsubscribe('nonexisting');
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

    public function testDelete()
    {
        $subscriber = $this->backend->getSubscriber('foo');

        $this->backend->createChannels(array(
            'a',
            'b',
            'c',
        ));

        $sId1 = $subscriber->subscribe('a')->getId();
        $sId2 = $subscriber->subscribe('b')->getId();
        $sId3 = $subscriber->subscribe('c')->getId();

        $subscriber->delete();

        foreach (array($sId1, $sId2, $sId3) as $subId) {
            try {
                $this->backend->getSubscription($subId);

                $this->fail("Subscription has not been deleted");
            } catch (SubscriptionDoesNotExistException $e) {
                $this->assertTrue(true, "Subscription has been deleted");
            }
        }
    }

    public function testFetchCursorOrder()
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
        $cursor = $subscriber->fetch();
        foreach ($cursor as $message) {
            $this->assertSame(++$i, $message->getContents());
        }

        $i = 10;
        $cursor = $subscriber->fetch();
        $cursor->setLimit(CursorInterface::LIMIT_NONE);
        $cursor->addSort(CursorInterface::FIELD_MSG_SENT, CursorInterface::SORT_DESC);
        foreach ($cursor as $message) {
          $this->assertSame(--$i, $message->getContents());
        }

        $i = 0;
        $cursor = $subscriber->fetch();
        $cursor->setRange(3, 0);
        foreach ($cursor as $message) {
            $this->assertSame(++$i, $message->getContents());
        }
        $this->assertEquals(3, $i);

        $i = 3;
        $cursor = $subscriber->fetch();
        $cursor->setRange(3, 3);
        foreach ($cursor as $message) {
          $this->assertSame(++$i, $message->getContents());
        }
        $this->assertEquals(3, $i - 3);
    }
}
