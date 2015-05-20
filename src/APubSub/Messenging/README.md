# Messenging API

This namespace contains an additional and optional API to handle user
private message threads using the APubSub library.

This namespace also serves as a good example of APubSub usage.

## Architecture

This API provides a single point of entry which is the *MessengingService*
class. It is supposed to live a singleton or a service into your application.

 *  Message threads are reprensented by the channels

 *  Thread recipients are represented by the channel subscribers

 *  Messages are messages

And that's pretty much it!
