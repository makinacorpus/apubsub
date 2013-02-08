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

    $service = some_singleton_get(); // Returns a NotificationService instance

    $service->registerListener(
        function(NotificationService $service, Notification $notification) {
            // Do you business here
        },
        NotificationService::EVENT_NOTIFY);

