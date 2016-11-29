<?php

namespace APubSub\Tests;

use APubSub\Field;

abstract class AbstractMessageTest extends AbstractBackendBasedTest
{
    public function testMessageReadStatus()
    {
        $chan       = $this->backend->createChannel("foo");
        $subscriber = $this->backend->getSubscriber("bar");
        $subscriber->subscribe("foo");

        $chan->send("Hello, World!", "message");

        // Test we can fetch the message
        $messages = $subscriber->fetch();
        foreach ($messages as $message) {

            $id = $message->getId();
            $this->assertTrue($message->isUnread());
            $this->assertSame("message", $message->getType());
            $this->assertNull($message->getReadTimestamp());
            $message->setUnread(false);

            break; // There should be only one
        }

        $messages = $subscriber->fetch();
        foreach ($messages as $message) {

            // Check the message is still here
            $this->assertSame($id, $message->getId());

            // Assert message is now unread
            $this->assertFalse($message->isUnread());

            // Assert message has now a read timestamp
            $this->assertNotNull($message->getReadTimestamp());

            break;
        }
    }

    public function testMassUpdate()
    {
        $chan = $this->backend->createChannel('a');
        $sub1 = $this->backend->getSubscriber('foo');
        $sub2 = $this->backend->getSubscriber('bar');

        $sub1->subscribe('a');
        $sub2->subscribe('a');

        $msg1 = $chan->send('1', 'original_type');
        $msg2 = $chan->send('2', 'original_type');
        $msg3 = $chan->send('3', 'original_type');
        $msg4 = $chan->send('4', 'original_type');
        $msg5 = $chan->send('5', 'original_type');

        // Test original messages state for suber 1
        $cursor = $sub1->fetch();
        $count = 0;
        /** @var \APubSub\MessageInstanceInterface $msg */
        foreach ($cursor as $msg) {
            $this->assertTrue($msg->isUnread());
            $this->assertNotSame(12, $msg->getReadTimestamp());
            ++$count;
        }
        $this->assertSame(5, $count);

        // And for suber 2
        $cursor = $sub2->fetch();
        $count = 0;
        /** @var \APubSub\MessageInstanceInterface $msg */
        foreach ($cursor as $msg) {
            $this->assertTrue($msg->isUnread());
            $this->assertNotSame(12, $msg->getReadTimestamp());
            ++$count;
        }
        $this->assertSame(5, $count);

        // Update suber 1 messages
        $cursor = $sub1->fetch();
        $cursor->update(array(
            Field::MSG_UNREAD => 0,
            Field::MSG_READ_TS => 12,
        ));

        // Ensures that suber 1 messages are modified
        $cursor = $sub1->fetch();
        $count = 0;
        /** @var \APubSub\MessageInstanceInterface $msg */
        foreach ($cursor as $msg) {
             $this->assertFalse($msg->isUnread());
             $this->assertSame(12, $msg->getReadTimestamp());
            ++$count;
        }
        $this->assertSame(5, $count);


        // But not those of suber 2
        $cursor = $sub2->fetch();
        $count = 0;
        /** @var \APubSub\MessageInstanceInterface $msg */
        foreach ($cursor as $msg) {
            $this->assertTrue($msg->isUnread());
            $this->assertSame('original_type', $msg->getType());
            ++$count;
        }
        $this->assertSame(5, $count);
    }
}
