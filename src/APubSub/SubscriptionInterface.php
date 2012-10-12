<?php

namespace APubSub;

/**
 * Defines a single subscription
 */
interface SubscriptionInterface
{
    /**
     * Get subscriber identifier
     *
     * @return scalar Subsriber identifier Can be any scalar type, depending
     *                                     on how the channel handles its
     *                                     subscribers
     */
    public function getId();

    /**
     * Get the originating channel
     *
     * @return \APubSub\ChannelInterface The channel this subscription is
     *                                   attached to
     */
    public function getChannel();

    /**
     * Does this subscription is still active
     *
     * @return bool Active state
     */
    public function isActive();

    /**
     * Get the UNIX timestamp when this subscription started
     *
     * @return int Unix timestamp
     */
    public function getStartTime();

    /**
     * Get the UNIX timestamp when this subscription stopped
     *
     * @return int               Unix timestamp
     *
     * @throws \RuntimeException If subscription is still active
     */
    public function getStopTime();

    /**
     * Delete all messages linked to that subscription and the subscription
     * itself. Once this call, you can get rid of the instance you have because
     * it doesn't exist anymore
     */
    public function delete();

    /**
     * Fetch current message queue
     *
     * @return Array of \APubSub\MessageInterface instances
     */
    public function fetch();

    /**
     * Deactivate this subscription, if it is already deactivated it will
     * remain silent
     */
    public function deactivate();

    /**
     * Activate this subscription, if it is already activate it will remain
     * silent
     */
    public function activate();
}
