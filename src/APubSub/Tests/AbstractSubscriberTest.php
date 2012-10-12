<?php

namespace APubSub\Tests;

abstract class AbstractSubscriberTest extends \PHPUnit_Framework_TestCase
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
        $subscriber = $this->channel->subscribe();
        $id         = $subscriber->getId();

        $this->assertFalse(empty($id));

        $channel = $subscriber->getChannel();
        // Depending on the implementation, the instance might not be the same
        // so only check for ids to be the same
        $this->assertSame($channel->getId(), $this->channel->getId());

        // Per definition a new subscription is always inactive
        $this->assertFalse($subscriber->isActive());

        try {
            $subscriber->getStartTime();
            $this->fail("Subscriber should not have a start time");
        } catch (\Exception $e) {
        }

        // Should not raise any exception
        $subscriber->getStopTime();

        $loaded = $this->backend->getSubscription($subscriber->getId());
        $this->assertSame(get_class($subscriber), get_class($loaded));
        $this->assertSame($subscriber->getId(), $loaded->getId());
        $this->assertFalse($loaded->isActive());

        $this->assertSame($this->channel->getId(), $subscriber->getChannel()->getId());
        $this->assertSame($this->channel->getId(), $loaded->getChannel()->getId());

        $subscriber->activate();

        try {
            $subscriber->getStartTime();
        } catch (\Exception $e) {
            $this->fail("Subscriber should have a start time");
        }
    }

    public function testFetch()
    {
        $subscriber = $this->channel->subscribe();
        $channel    = $subscriber->getChannel();

        $channel->send($channel->createMessage(42));

        $messages = $subscriber->fetch();
        $this->assertTrue(empty($messages));

        $subscriber->activate();
        $channel->send($channel->createMessage(24));
        $message = $subscriber->fetch();
        $this->assertFalse(empty($messages));
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);

        $i = 0;
        foreach ($messages as $fetched) {
            if ($i > 1) {
                throw new \Exception("There should be only one item in the queue");
            }
            $this->assertSame(24, $fetched->getContents());
            ++$i;
        }

        $message = $subscriber->fetch();
        $this->assertFalse(empty($messages));
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        foreach ($messages as $fetched) {
            throw new \Exception("Queue was supposed to be emptied by the fetch() call");
        }

        $subscriber->deactivate();
        $channel->send($channel->createMessage(12));
        $message = $subscriber->fetch();
        $this->assertFalse(empty($messages));
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);
        foreach ($messages as $fetched) {
            throw new \Exception("Subscriber is supposedly inactive");
        }
    }
}
