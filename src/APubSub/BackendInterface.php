<?php

namespace APubSub;

/**
 * Backend entry point: allows you to retrieve and create channels and
 * subscriptions.
 */
interface BackendInterface extends
    ObjectInterface,
    MessageContainerInterface
{
    /**
     * Helper method for loading one single channel
     *
     * @param string $channelId             Channel identifier
     *
     * @return ChannelInterface             Loaded channel
     *
     * @throws ChannelDoesNotExistException If channel does not exist
     */
    public function getChannel($id);

    /**
     * Fetch channels
     *
     * @param array $conditions                            The channel id
     *
     * @return \APubSub\CursorInterface                    Channel cursor
     */
    public function fetchChannels(array $conditions = null);

    /**
     * Create a channel instance
     *
     * @param string $id           Channel name
     * @param string $ignoreErrors Allow silent errors when channel already
     *                             exists
     *
     * @return ChannelInterface    A ready to use channel instance
     *
     * @throws \APubSub\Error\ChannelAlreadyExistsException
     *                             If the channel with the given identifier
     *                             already exists
     */
    public function createChannel($id, $ignoreErrors = false);

    /**
     * Create multiple channel instances
     *
     * @param string $idList       List of channel names
     * @param string $ignoreErrors Allow silent errors when channel already
     *                             exists
     *
     * @return array|Traversable   List of created channel instances
     *
     * @throws \APubSub\Error\ChannelAlreadyExistsException
     *                             If a channel already exists and errors are
     *                             not ignored
     */
    public function createChannels($idList, $ignoreErrors = false);

    /**
     * Load an existing subscription
     *
     * @param scalar $id                      The subscription identifier
     *
     * @return \APubSub\SubscriptionInterface The subscription instance
     *
     * @throws \APubSub\Error\SubscriptionDoesNotExistException
     *                                        If the subscription does not exist
     */
    public function getSubscription($id);

    /**
     * Load existing subscriptions
     *
     * @param array $idList      List of subscription ids to load
     *
     * @return array|Traversalbe List of subscription instances
     *
     * @throws \APubSub\Error\SubscriptionDoesNotExistException
     *                           If one of the subscriptions does not exist
     */
    public function getSubscriptions($idList);

    /**
     * Delete a channel along with all its messages and subscriptions
     *
     * @param string $id Subscription identifier
     *
     * @throws \APubSub\Error\SubscriptionDoesNotExistException
     *                   If subscription does not exist
     */
    public function deleteSubscription($id);

    /**
     * Delete a list of subscriptions
     *
     * @param array|Traversable $idList Subscription identifiers
     *
     * @throws \APubSub\Error\SubscriptionDoesNotExistException
     *                                  If one subscription does not exist, case
     *                                  in which the operation ended up
     *                                  imcomplete
     */
    public function deleteSubscriptions($idList);

    /**
     * Get or create a new subscriber instance
     *
     * @param scalar $id                    Scalar value (will stored as a
     *                                      string) which must be unique
     *
     * @return \APubSub\SubscriberInterface The subscriber instance
     */
    public function getSubscriber($id);

    /**
     * Delete a subscriber along all its susbscriptions
     *
     * @param string $id Subscriber identifier
     *
     * @throws \APubSub\Error\SubscriberDoesNotExistException
     *                   If subscription does not exist
     */
    public function deleteSubscriber($id);

    /**
     * Delete a list of subscribers
     *
     * @param array|Traversable $idList Subscriber identifiers
     *
     * @throws \APubSub\Error\SubscriberDoesNotExistException
     *                                  If one subscription does not exist, case
     *                                  in which the operation ended up
     *                                  imcomplete
     */
    public function deleteSubscribers($idList);

    /**
     * Create a new subscription to the given channel
     *
     * The counterpart of this method is the deleteSubscription() method
     *
     * @param string $chanId       Channel identifier
     * @param string $subscriberId Subscriber identifier
     *
     * @return SubscriptionInterface The new subscription object, which is not
     *                               active per default and whose identifier
     *                               has been generated
     */
    public function subscribe($chanId, $subscriberId = null);

    /**
     * Get subscriber list helper if this backend implements it
     *
     * @param array $conditions  Array of key value pairs conditions, only the
     *                           "equal" operation is supported. If value is an
     *                           array, treat it as a "IN" operator
     *
     * @return CursorInterface   Cursor
     */
    public function fetchSubscribers(array $conditions = null);

    /**
     * Send a single message to one or more channels
     *
     * @param string|string[] $chanId List of channels or single channel to send
     *                                the message too
     * @param string $type            Message type
     * @param int $level              Arbitrary business level
     * @param int $sendTime           If set the creation/send timestamp will be
     *                                forced to the given value
     */
    public function send($chanId, $contents, $type = null, $level = 0, $sendTime = null);

    /**
     * Set the unread status of a specific message
     *
     * Method is silent if message does not exist in this subscription queue
     *
     * @param scalar $queueId Message identifier in queue
     * @param bool $toggle    True for unread, false for read
     */
    public function setUnread($queueId, $toggle = false);

    /**
     * Flush any non essential internal cache this backend may hold. Backends
     * implementor can choose to do nothing on such call
     */
    public function flushCaches();

    /**
     * Run garbage collection on backend data
     *
     * This API is supposed to provide high performance methods, thus some
     * outdated data might be left on the backend in order to go faster during
     * runtime: this garbage collection might be called arbitrarily by the
     * software using it, and will be run in off peak time in order to let the
     * backend do heavy operations
     */
    public function garbageCollection();

    /**
     * Get a set of random information about the backend state
     *
     * Backends can return null here if no analysis information can fetched or
     * if this method is not implemented/not important
     *
     * @return array Key/value pairs, keys are english names and values are
     *               any value you would ever want to display. String values
     *               can be prone to translation attempts
     */
    public function getAnalysis();

    /**
     * Set backend specific options
     *
     * @param array $options Options to set
     */
    public function setOptions(array $options);
}
