<?php

namespace MakinaCorpus\APubSub;

use MakinaCorpus\APubSub\Error\ChannelAlreadyExistsException;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Error\SubscriptionDoesNotExistException;

/**
 * Backend entry point: allows you to retrieve and create channels and
 * subscriptions.
 */
interface BackendInterface extends MessageContainerInterface
{
    /**
     * Helper method for loading one single channel
     *
     * @param string $id
     *
     * @return ChannelInterface
     *
     * @throws ChannelDoesNotExistException
     */
    public function getChannel($id);

    /**
     * Helper method for loading mulitple channels
     *
     * @param string[] $idList
     *
     * @return ChannelInterface[]
     *
     * @throws ChannelDoesNotExistException
     */
    public function getChannels($idList);

    /**
     * Fetch channels
     *
     * @param array $conditions
     *
     * @return CursorInterface|ChannelInterface[]
     *   Channel cursor
     */
    public function fetchChannels(array $conditions = null);

    /**
     * Create a channel instance
     *
     * @param string $id
     *   Channel name
     * @param string $title
     *   Human readable title
     * @param string $ignoreErrors
     *   Allow silent errors when channel already exists
     *
     * @return ChannelInterface
     *
     * @throws ChannelAlreadyExistsException
     */
    public function createChannel($id, $title = null, $ignoreErrors = false);

    /**
     * Create multiple channel instances
     *
     * @param string[] $idList
     *   List of channel names
     * @param string $ignoreErrors
     *   Allow silent errors when channel already exists
     *
     * @return ChannelInterface[]
     *   List of created channel instances
     *
     * @throws ChannelAlreadyExistsException
     */
    public function createChannels($idList, $ignoreErrors = false);

    /**
     * Delete channel
     *
     * If channel does not exists be silent about it
     *
     * @param string $id
     * @param string $ignoreErrors
     */
    public function deleteChannel($id, $ignoreErrors = false);

    /**
     * Load an existing subscription
     *
     * @param int $id
     *
     * @return SubscriptionInterface
     *
     * @throws SubscriptionDoesNotExistException
     */
    public function getSubscription($id);

    /**
     * Load existing subscriptions
     *
     * @param array $idList
     *
     * @return int[]
     *
     * @throws SubscriptionDoesNotExistException
     */
    public function getSubscriptions($idList);

    /**
     * Fetch subscriptions
     *
     * @param array $conditions
     *
     * @return CursorInterface|SubscriptionInterface[]
     */
    public function fetchSubscriptions(array $conditions = null);

    /**
     * Get or create a new subscriber instance
     *
     * @param scalar $id
     *
     * @return SubscriberInterface
     */
    public function getSubscriber($id);

    /**
     * Delete a subscriber along all its susbscriptions
     *
     * @param string $id Subscriber identifier
     */
    public function deleteSubscriber($id);

    /**
     * Delete a list of subscribers
     *
     * @param string[] $idList Subscriber identifiers
     */
    public function deleteSubscribers($idList);

    /**
     * Create a new subscription to the given channel
     *
     * @param string $chanId
     * @param string $subscriberId
     *
     * @return SubscriptionInterface
     *   The new subscription object, which is not active per default and whose
     *   identifier has been generated
     */
    public function subscribe($chanId, $subscriberId = null);

    /**
     * Get subscriber list helper if this backend implements it
     *
     * @param array $conditions
     *   Array of key value pairs conditions, only the "equal" operation is
     *   supported. If value is an array, treat it as a "IN" operator
     *
     * @return CursorInterface|SubscriberInterface[]
     */
    public function fetchSubscribers(array $conditions = null);

    /**
     * Send a single message to one or more channels
     *
     * @param string|string[] $chanId
     *   List of channels or single channel to send the message too
     * @param string $type
     *   Message type
     * @param string $origin
     *   Arbitrary origin text representation
     * @param int $level
     *   Arbitrary business level
     * @param scalar[] $exclude
     *   Excluded subscription identifiers to the send recipients
     * @param \DateTime $sentAt
     *   If set the sent date will be forced to the given value
     */
    public function send(
        $chanId,
        $contents,
        $type               = null,
        $origin             = null,
        $level              = 0,
        array $exclude      = null,
        \DateTime $sentAt   = null
    );

    /**
     * Copy old messages from one channel to one or more new subscriptions
     *
     * @param int $chanId
     *   Channel identifier (message selection)
     * @param int|int[] $subIdList
     *   Target subscription identifier
     * @param boolean $isUnread
     *   Default (un)read state for new messages
     */
    public function copyQueue($chanId, $subIdList, $isUnread = true);

    /**
     * Set the unread status of a specific message
     *
     * Method is silent if message does not exist in this subscription queue
     *
     * @param scalar $queueId
     *   Message identifier in queue
     * @param bool $toggle
     *   True for unread, false for read
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
     * Get a set of arbitrary information about the backend state
     *
     * Backends can return null here if no analysis information can fetched or
     * if this method is not implemented/not important
     *
     * @return array
     *   Key/value pairs, keys are english names and values are any value you
     *   would ever want to display. String values can be prone to translation
     *   attempts
     */
    public function getAnalysis();
}
