<?php

namespace APubSub\Tests;

abstract class AbstractChannelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \APubSub\PubSubInterface
     */
    protected $backend;

    /**
     * Create the backend for testing
     *
     * @return \APubSub\PubSubInterface Ready to use mock instance
     */
    abstract protected function setUpBackend();

    protected function setUp()
    {
        $this->backend = $this->setUpBackend();
    }

    public function testChannelCreation()
    {
        $channel = $this->backend->createChannel('foo');
        $loaded  = $this->backend->getChannel('foo');

        $this->assertSame($channel->getId(), 'foo');
        $this->assertSame($loaded->getId(), 'foo');
    }

    public function testMessageCreation()
    {
        $channel  = $this->backend->createChannel('foo');
        $contents = array('test' => 12);

        $message = $channel->createMessage($contents);

        $this->assertSame($contents, $message->getContents());

        try {
            $message->getId();
            $this->fail("Message should not have an id");
        } catch (\Exception $e) {
        }
    }

    public function testMessageSendToSubscriber()
    {
        $channel  = $this->backend->createChannel('foo');
        $contents = array('test' => 12);

        $subscriber = $channel->subscribe();
        $this->assertNotNull($subscriber->getId());

        $subscriber->activate();

        $message = $channel->createMessage($contents);
        $channel->send($message);

        $id = $message->getId();

        $messages = $subscriber->fetch();
        $this->assertFalse(empty($messages));
        $this->assertTrue(is_array($messages) || $messages instanceof \Traversable);

        foreach ($messages as $fetched) {
            $this->assertSame($contents, $fetched->getContents());
            $this->assertSame($id, $fetched->getId());
            break; // Only test the first
        }
    }
}
