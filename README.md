Async PubSub
============

Provides an asynchronous PubSub like generic API.

Getting started
===============

In order to follow this tutorial, you need to have a fully setup and working
backend available. Setup and object instanciation will depend upon the choosen
backend, so we will consider that the $pubsub object has already been created.

    // This is our object:
    $pubsub = new SomePubSubBackend();

Creating a channel
------------------

    // Simply create the new channel
    $channel = $pubsub->createChannel("foo");
    $channel = $pubsub->createChannel("bar");
    $channel = $pubsub->createChannel("baz");

Creating a message
------------------

Creating a message is probably the most simple operation of all.

    // Retrieve our channel
    $channel = $pubsub->getChannel("foo");

    // And yet it is simple as that
    $channel->send("Hello, World!");

Using the SubscriberInterface
-----------------------------

The subscriber is an helper that will keep for you the business mappings between
your business identifier and the subscriptions you made for it. This is the most
simple way to use this API and will fit most purposes.

The subscriber is a transient object which is not materialized into database. As
soon as you make it subscribe to channels, the mapping will be kept, but as soon
as you unsubscribe it the mapping will be deleted.

    // Create a new subscriber
    $subscriber = $pubsub->getSubscriber("my_business_id");

    // Subscribe to a channel, unlike the subscription, subscribing via the
    // subscriber object will directly activate the new subscription
    $subscriber->subscribe("foo");
    $subscriber->subscribe("bar");
    $subscriber->subscribe("baz");

    // Fetching subscriber messages
    $messages = $subscriber->fetch();

Note that messages will be fetched from all active subscriptions and be sorted
by creation date

If you need to do more advanced operations on the subscriber subscriptions, you
can fetch the subscriptions instances and work directly with it:

    $subscription = $subscriber->getSubscription("bar");

    // For example, deactivate a subscription without deleting it
    $subscription->deactivate();

    // Or delete another one
    $subscriber
        ->getSubscription("baz")
        ->delete();

The real use case where you'd need a subscriber instead of working directly
with subscriptions is when you need to fetch messages from multiple channels
at once (for example, user notifications in a social network).

It can also fetch only a limited number of messages:

    // Fetch the 10 oldest messages in queue
    $messages = $subscriber->fetchHead(10);

    // Fetch the 20 most recent messages in queue
    $messages = $subscriber->fetchTail(20);

Working directly with subscription
----------------------------------

Additionally you can bypass the subscriber instance and work only with
subscriptions. This method is recommended if you can store the subscription
identifiers in your business layer: it will provide best performances since it
does not need to keep a mapping on its own.

Subscribing to a channel is basically two things: first create a subscription
attached to this channel -a subscription cannot exist without a channel- then
activating it.

A subscription instance will always carry an identifier, whose type depends on
the backend (but which always will be a PHP primitive scalar type). This
identifier is the link you need to store in your business  layer in order to be
able to later fetch messages or deactivate it: you need to store it by yourself
on a higher level.

    // Retrieve our channel
    $channel = $pubsub->getChannel("foo");

    // Create the new subscription
    $subscription = $channel->subscribe();

    // Per default, the subscription is inactive in order to avoid data bloat
    // when a subscription is accidentally created
    $subscription->activate();

    // At this point, you must keep the subscription identifier, else it will
    // be lost forever
    $subscriptionId = $subscription->getId();

Fetching new messages
---------------------

Fetching new messages will only be possible on a per subscription basis. Note
that when you fetch messages, the new message queue is emptied and you cannot
get them anymore as they will be deleted or marked for deletion. The backend may
or may not provide helpers to fetch them after that.

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

Making messages persistent on business side
-------------------------------------------

The _ApbX_ (_X_ as in eXternal) namespace will give you some optional helpers:
the one you're looking for is the _ApbX\LocalCache\MessageQueueInterface_. This
object aims to provide a simple and serializable message queue. One default
implementation is provided: _ApbX\LocalCache\LRUMessageQueue_.

The idea is that when you fetch messages for a given subscription or subscriber
the backend will drop them per design, while fetching them you will be able to
store them into the queue:

    use ApbX\LocalCache\LRUMessageQueue

    function getQueue($name)
    {
        // Depending on your framework you might want to do things differently
        static $queues;

        if (isset($queues[$name])) {
            return $queues[$name];
        }

        $queueSize  = 30;
        $subscriber = $backend->getSubscriber($name);

        // Fetch you stored instance from anywhere you'd want to store it for
        // the sake of example we will use APC user cache
        $queue = apc_fetch($name);

        if (!$queue) {
            // Create the new queue, it never has been stored before
            $queue = LRUMessageQueue(30);
        }

        $messages = $subscriber->fetch();

        if (!empty($messages)) {
            $messages->prependAll($messages);
        }

        // The message queue is able to determine if it has been modified or
        // not, including modifications on messages instances. For performance
        // reasons you might want to store again the queue only if modified
        register_shutdown_function(function () use ($queue, $name) {
            if ($queue->isModified()) {
                apc_store($name, $queue);
            }
        });

        return $queues[$name] = $queue;
    }

The queue object will be your new interface for working with messages: let's
assume you have a user 'foo' which is a subscriber, and you want to display
all his subscribed channel messages:

    $username = 'foo';

    $queue = getQueue($username);

    if (!$queue->isEmpty()) {
        foreach ($queue as $message) {
            if ($message->isUnread()) {
                // Do something with your message
                // The next line will actually set the queue modified state
                // to true, allowing it to be saved in the shutdown handler
                $messagee->setReadStatus(true);
            } else {
                // Do something else with your message
            }
        }
    } else {
        echo "You have no messages!";
    }

You'll notice that messages in the queue are not the original instances from
the backend but a specialization instead.

Performance considerations
==========================

General matters
---------------

Performance will highly depend upon the backend implementation. For example, the
Drupal 7 implementation is not optimized to be used with subscribers, but will
be much more efficient by using directly subscriptions instead.

Good pratices
-------------

The provided default backend will attempt lazy loading whenever possible: please
consider that you should never get an object instance when not necessary.

For example, if you need a channel identifier from a message, do not do this:

    $chanId = $message->getChannel()->getId();

But always do this instead:

    $chanId = $message->getChannelId();

Because the channel is lazy loaded on demand by the default implementation.
