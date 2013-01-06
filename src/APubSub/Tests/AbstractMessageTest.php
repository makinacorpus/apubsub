<?php

namespace APubSub\Tests;

abstract class AbstractMessageTest extends AbstractBackendBasedTest
{
    public function testMessageReadStatus()
    {
        $channel    = $this->backend->createChannel("foo");
        $subscriber = $this->backend->getSubscriber("bar");
        $subscriber->subscribe("foo");

        $channel->send("Hello, World!");

        // Test we can fetch the message
        $messages = $subscriber->fetch();
        foreach ($messages as $message) {

            $id = $message->getId();
            $this->assertTrue($message->isUnread());
            $message->setUnread(false);

            break; // There should be only one
        }

        $messages = $subscriber->fetch();
        foreach ($messages as $message) {

            // Check the message is still here
            $this->assertSame($id, $message->getId());

            // Assert message is now unread
            $this->assertFalse($message->isUnread());

            break;
        }
    }

    public function testMessageCursor()
    {
        
    }
}
