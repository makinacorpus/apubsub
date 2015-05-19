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