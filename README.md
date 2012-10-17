Async PubSub
============

Provides an asynchronous PubSub like generic API.

Usage
=====

Creating a channel
------------------

In order to create a channel, you need to have a fully setup and working
backend available. Setup and object instanciation will depend upon the choosen
backend, so we will consider that the $pubsub object has already been created.

    // This is our object:
    $pubsub = new SomePubSubBackend();

Creating a channel
------------------

    // Simply create the new channel
    $channel = $pubsub->createChannel("system_events");

Subscribing
-----------

Subscribing to a channel is basically two things: first create a subscription
attached to this channel -a subscription cannot exist without a channel- then
activating it.

A subscription instance will always carry an identifier, whose type depends on
the backend (but which always will be a PHP primitive scalar type). This
identifier is the link to the whole API you need to keep in your business 
layer in order to be able to later fetch messages or deactivate it: you need
to store it by yourself on a higher level.

    // Retrieve our channel
    $channel = $pubsub->getChannel("system_events");

    // Create the new subscription
    $subscription = $channel->subscribe();

    // Per default, the subscription is inactive in order to avoid data bloat
    // when a subscription is accidentally created
    $subscription->activate();

    // At this point, you must keep the subscription identifier, else it will
    // be lost forever
    $subscriptionId = $subscription->getId();

Creating a message
------------------

Creating a message is probably the most simple operation of all.

    // Retrieve our channel
    $channel = $pubsub->getChannel("system_events");

    // And yet it is simple as that
    $channel->send("Hello, World!");

Fetching new messages
---------------------

Fetching new messages will only be possible on a subscription basis. Note that
when you fetch messages, the new message queue is emptied and you cannot get
them anymore, so even if the backend is configured to keep them. The backend
may or may not provide helpers to fetch them once again if they are kept.

    // Retrieve the subscription: the identifier here is the one you kept
    // and stored when you created the subscription
    $subscription = $pubsub->getSubscription($subscriptionId);

    // Fetch operation will only get new messages. The backend may or may not
    // let you keep the messages stored depending on both the backend capability
    // and configuration
    $messages = $subscription->fetch();

If you're dealing with user notifications for example, and want to keep them
persistent for a while, you'll need to store the messages into your own business
API.
