<?php

namespace APubSub\Tests\Notification;

use APubSub\Notification\NotificationService;

abstract class AbstractNotificationServiceTest extends AbstractNotificationBasedTest
{
    public function testSubscribeAndNotify()
    {
        $service = $this->getService();

        $userId1 = $service->getSubscriberName("u", 1);
        $userId2 = $service->getSubscriberName("u", 2);
        $userId3 = $service->getSubscriberName("u", 3);

        $chanId1 = $service->getChanId("friend", 1);
        $chanId2 = $service->getChanId("friend", 2);
        $chanId3 = $service->getChanId("friend", 3);

        // User 1 registers to user 2 and user 3 channels
        // because they are his friends
        $service->subscribe($chanId2, $userId1);
        $service->subscribe($chanId1, $userId2);
        $service->subscribe($chanId3, $userId1);
        $service->subscribe($chanId1, $userId3);

        // Now they are all linked let's just try to send stuff
        $service->notify($chanId1, "friend");

        // Everyone should have received that
        foreach (array($userId2, $userId3) as $userId) {
            $messages = $service->getSubscriber($userId)->fetch();
            $this->assertCount(1, $messages);
            foreach ($messages as $message) {
                // There should be only 1 right?
                $this->assertSame("friend", $message->getType());
            }
        }

        // Now 2 and 3 become friends, and we send a multichannel
        // message there
        $service->subscribe($chanId2, $userId3);
        $service->subscribe($chanId3, $userId2);

        // Multichannel message
        $service->notify(
            array(
                $chanId2,
                $chanId3,
            ),
            "friend",
            array(
                "message" => "2 and 3 are friends yay",
            )
        );

        // 1 should have received it only once
        // This also tests the multi channel message feature
        $messages = $service->getSubscriber($userId1)->fetch();
        $this->assertCount(1, $messages);
        foreach ($messages as $message) {
            // There should be only 1 right?
            $this->assertSame("friend", $message->getType());
        }

        // 2 and 3 would have received it too, but they now have 2 messages
        foreach (array($userId2, $userId3) as $userId) {
            $messages = $service->getSubscriber($userId)->fetch();
            $this->assertCount(2, $messages);
            foreach ($messages as $message) {
                // There should be only 1 right?
                $this->assertSame("friend", $message->getType());
            }
            // Ensure latest message has the right content
            $notification = $service->getNotification($message);
            $this->assertSame("2 and 3 are friends yay", $notification->get("message"));
        }
    }
}
