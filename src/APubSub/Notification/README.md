# Notification API

This namespace contains an additional and optional API to handle user
notifications using the APubSub library.

This namespace also serves as a good example of APubSub usage.

## Architecture

This API provides a single point of entry which is the *NotificationService*
class. It is supposed to live a singleton or a service into your application.

This object exposes the *notify()* method allowing you to send messages. Those
messages will be encapsulated internally into *Notification* objects providing
the user some helpers for message formatting.

There is two new concepts bring with it for this to work:

* Notification *formatters*: also refered as *notification types* these
  objects are registered to the manager and will provide the messages
  formatting.

* *Channel types*: these objects are registered to the manager too and only
  provide a way to categorize your *channels*, this serves the purpose of
  providing a more complete UI to the final user.

## Queues

You may experience the need to enabled various other communication routes than
pure notifications (for example, send notifications via mail), a tool is a your
disposable for this: *queues*.

## Presentation

The idea behind a communication subscriber is to maintain a 1..n relationship
between the original subscriber (e.g. the human user of your system) and the
various communication routes he can use. Each one of this *queue* will
subscribe to the exact same channels the user has chosen.

Now imagine a site where you have the web notifications (what this API brings)
plus those other two communication routes:

* Mails (identifier by *mail* in the system)

* Phone texts (*sms* in the system)

Now let's consider the user "Foo" (with id 42, which makes him being the
subscriber with identifier *u:42*) which subscribed to the following channels:

1. System messages

2. Friends messages

3. News feed

4. Comments on an article about lol cats

And wishes the first 2 also send mail notices, and the last one (he loves lol
cats) sends to him phone texts, we could create the two following subscribers:

* *mail:42* which also subscribes to channels 1 and 2

* *sms:42* which also subscribes to channel 4

## Features

Queues are meant to be processed in asynchroneous threads. This can be any kind
of system or applicative batch (cron or any other).

## Usage

In order to use this, you need to ensure three steps.

### Implement the queue

All you need to do is extend the QueueInterface:

    use APubSub\Notification\QueueInterface;
    use APubSub\Notification\Queue\AbstractComQueue;

    class VirtualQueue extends AbstractQueue implements QueueInterface
    {
        // ...
    }

### Register the queue

Register all your queues to the notification service:

    use APubSub\Notification\NotificationService;

    $service = get_my_service(); // Returns a NotificationService instance

    $service
        ->getQueueRegistry()
        ->registerInstance(new VirtualQueue());

### Ensure the associated subscribers when user asks it

From this point, this is your responsability to create the queues depending
on user settings. Note that you can use the SubscriberInterface extra data
to store the user settings.

### Registering routes

## Event sending

The *APubSub* library only provide methods for mass messenging, all operations
are supposed to be aggregate operations. This is built around the relational
model. Main downside for notification management is that we cannot operate over
each single queued message (one message attached to one subscription) making it
impossible to manage simple things such as sending one mail for one subscriber.

In order to solve that problem, the *NotificationService* instance provides the
ability to register listeners throught the *registerListener* method, allowing
to catch the notify event.

For example, consider that you want to push a mail for each user receiving the
message:

    use APubSub\Notification\Notification;
    use APubSub\Notification\NotificationService;

    $service = get_my_service(); // Returns a NotificationService instance

    $service->registerListener(
        function(NotificationService $service, Notification $notification) {
            // Do you business here
        },
        NotificationService::EVENT_NOTIFY);

