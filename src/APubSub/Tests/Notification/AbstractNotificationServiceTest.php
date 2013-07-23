<?php

namespace APubSub\Tests\Notification;

use APubSub\Notification\NotificationService;

abstract class AbstractNotificationServiceTest extends AbstractNotificationBasedTest
{
    public function testSubscribeAndNotify()
    {
        $user1Id     = 1;
        $user2Id     = 2;
        $content1Id  = 1;

        // Virtually create some channels, note that they don't exist yet
        // and we won't ever create them manually, notification service will
        // do it for us
        $user1ChanId    = $this->service->getChanId('friend', $user1Id);
        $user2ChanId    = $this->service->getChanId('friend', $user2Id);
        $content1ChanId = $this->service->getChanId('content', $content1Id);

        // Let's make user 1 subscribe to content 1
        $this->service->subscribe($content1ChanId, $user1Id);
        // And user 2 subscribe to user 1 friend channel
        $this->service->subscribe($user1ChanId, $user2Id);

        // User 1 did some thing, user 2 should receive a notification
        $this->service->notify('friend', $user1Id, "gruik");
        $subUser2 = $this->service->getSubscriber($user2Id);
        $cursor   = $subUser2->fetch();
        $this->assertCount(1, $cursor);

        // Get first element and ensure data is OK
        foreach ($cursor as $message) {
            $notification = $this->service->getNotification($message);

            // Ensures that notification inherits from message
            $this->assertEquals($notification->getMessageId(), $message->getId());

            // Is valid means this message originates from
            // NotificationService::notify()
            $this->assertTrue($notification->isValid());

            $data = $notification->getData();
            $this->assertEquals($data, "gruik");
        }

        // Content was modified, user 1 should receive a notification
        $this->service->notify('content', $content1Id, array('some' => 'data'));
        $subUser1 = $this->service->getSubscriber($user1Id);
        $cursor   = $subUser1->fetch();
        $this->assertCount(1, $cursor);

        // Get first element and ensure data is OK
        foreach ($cursor as $message) {
            $notification = $this->service->getNotification($message);

            // Ensures that notification inherits from message
            $this->assertEquals($notification->getMessageId(), $message->getId());

            // Is valid means this message originates from
            // NotificationService::notify()
            $this->assertTrue($notification->isValid());

            $data = $notification->getData();
            $this->assertCount(1, $data);
            $this->assertEquals($data['some'], "data");
        }
    }

    /**
     * Ensures that notifications on disabled types are never sent
     */
    function testDisabledType()
    {
        $chanId = $this->service->getChanId('disabled', 1);
        // Implicit creation of subscriber
        // User 1 subscribe to this new chan
        $this->service->subscribe($chanId, 1);

        $sub = $this->service->getSubscriber(1);
        $this->assertTrue($sub->hasSubscriptionFor($chanId));

        // Notify someone here that something happened
        $this->service->notify('disabled', 1, array('some' => 'data'));

        // Type is disabled therefore no notifications should happen here
        $cursor = $sub->fetch();

        $this->assertCount(0, $cursor);
    }

    /**
     * Ensures that notifications on non existing types are never sent (same
     * as upper)
     */
    function testNonExistingType()
    {
        $chanId = $this->service->getChanId('nonexisting', 1);
        // Implicit creation of subscriber
        // User 1 subscribe to this new chan
        $this->service->subscribe($chanId, 1);

        $sub = $this->service->getSubscriber(1);
        // Channel should be implicitely created no matter what happens
        $this->assertTrue($sub->hasSubscriptionFor($chanId));

        // Notify someone here that something happened
        $this->service->notify('nonexisting', 1, array('some' => 'data'));

        // Type is disabled therefore no notifications should happen here
        $cursor = $sub->fetch();

        $this->assertCount(0, $cursor);
    }
}
