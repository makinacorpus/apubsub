<?php

namespace APubSub;

/**
 * Backend entry point: allows you to retrieve and create channels and
 * subscriptions.
 */
interface PubSubInterface
{
    /**
     * Load an existing channel
     *
     * @param scalar $id                                   The channel id
     *
     * @return \APubSub\ChannelInterface                   The channel instance
     *
     * @throws \APubSub\Error\ChannelDoesNotExistException If the channel does
     *                                                     not exist
     */
    public function getChannel($id);

    /**
     * Create a channel instance
     *
     * @param string $id        Channel name
     *
     * @return ChannelInterface A ready to use channel instance
     *
     * @throws \APubSub\Error\ChannelAlreadyExistsException
     *                          If the channel with the given identifier already
     *                          exists
     */
    public function createChannel($id);

    /**
     * Delete a channel along with all its messages and subscriptions
     *
     * @param string $id Channel identifier
     *
     * @throws \APubSub\Error\ChannelDoesNotExistException
     *                   If channel does not exist
     */
    public function deleteChannel($id);

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
     * Delete a channel along with all its messages and subscriptions
     *
     * It is eventually an alias of SubscriptionInterface::delete()
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
}
