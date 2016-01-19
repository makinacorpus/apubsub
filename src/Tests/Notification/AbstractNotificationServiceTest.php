<?php

namespace MakinaCorpus\APubSub\Tests\Notification;

use MakinaCorpus\APubSub\Notification\DefaultNotification;

abstract class AbstractNotificationServiceTest extends AbstractNotificationBasedTest
{
    public function testSubscribeAndNotify()
    {
        $service = $this->getService();

        // User 1 knows both user 2 and 3
        $service->subscribe('friend', [2, 3], 1);
        // User 2 knows user 1
        $service->subscribe('friend', 1, 2);
        // User 3 knows user 1
        $service->subscribe('friend', 1, 3);

        // Now they are all linked let's just try to send stuff
        $service->notify('friend', 1, 'something', ['raw' => 'This is something']);

        // Everyone should have received that
        foreach ([2, 3] as $id) {

            $notifications = $service->fetchForSubscriber($id);

            $this->assertCount(1, $notifications);

            foreach ($notifications as $notification) {
                // Ensures that type is propagated
                $this->assertSame('friend', $notification->getResourceType());
                // Ensures that resource identifiers are propagated
                $this->assertSame([1], $notification->getResourceIdList());
                // Ensures that action is correct too
                $this->assertSame('something', $notification->getAction());

                // Ensure formatter, use a trick I learnt from Ocramius, see
                //  https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
                // Thank you dude! Wrong but working...
                $thief = function (DefaultNotification $notification) {
                    return $notification->formatter;
                };
                $thief = \Closure::bind($thief, null, $notification);
                $formatter = $thief($notification);
                $this->assertInstanceOf('\MakinaCorpus\APubSub\Notification\Formatter\RawFormatter', $formatter);
            }
        }

        // Now 2 and 3 become friends
        $service->subscribe('friend', 2, 3);
        $service->subscribe('friend', 3, 2);

        // Multichannel message, on both 2 and 3!
        $textMessage = '2 and 3 are friend now yeah';
        $service->notify('friend', [2, 3], 'friended', ['raw' => $textMessage]);

        // 1 should have received it only once
        // This also tests the multi channel message feature
        $notifications = $service->fetchForSubscriber(1);
        $this->assertCount(1, $notifications);
        foreach ($notifications as $notification) {
            // Also check that formatting works
            $this->assertSame($textMessage, $notification->format());
        }

        // 2 and 3 would have received it too, but they now have 2 messages
        foreach ([2, 3] as $id) {
            $notifications = $service->fetchForSubscriber($id);
            $this->assertCount(2, $notifications);

            foreach ($notifications as $notification) {
                // This also ensures the notification has the right content
                $this->assertSame($textMessage, $notification->format());
                // Default order is desc, so iterate only over the first one that
                // will match, the second being the friend:something sent upper
                break;
            }
        }
    }

    public function testCurrentSubscriberExclusion()
    {
        $service = $this->getService();

        // Set context to user 4 and send some stuff
        $service->addCurrentSubscriber(4);

        // Register everyone *after* we actually did fetched the subscriber
        // hoping this would work gracefully: this prooves that subscriber
        // instance is the same between various load calls; This probably
        // should not be tested here, but that's actually an excellent use
        // case for this, since setting the context will happen long enough
        // in the runtime of a normal user oriented application, and the
        // same user could subscribe after that
        $service->subscribe('friend', 4, 4);
        $service->subscribe('friend', 4, 5);
        $service->subscribe('friend', 4, 6);

        $service->notify('friend', 4, 'something');

        $messages = $service->fetchForSubscriber(4);
        $this->assertCount(0, $messages);
        $messages = $service->fetchForSubscriber(5);
        $this->assertCount(1, $messages);
        $messages = $service->fetchForSubscriber(6);
        $this->assertCount(1, $messages);
    }
}
