<?php

namespace APubSub;

/**
 * Defines a single subscription
 */
interface SubscriptionInterface extends MessageContainerInterface
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
     * Get creation time as a UNIX timestamp
     *
     * @return int UNIX timestamp where the channel was created
     */
    public function getCreationTime();

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
     * Deactivate this subscription, if it is already deactivated it will
     * remain silent
     */
    public function deactivate();

    /**
     * Activate this subscription, if it is already activate it will remain
     * silent
     */
    public function activate();

    /**
     * Get channel identifier
     *
     * Use this method rather than getChannel() whenever possible, in order to
     * avoid backends lookup in most implementations
     *
     * @return string
     */
    public function getChannelId();
}
