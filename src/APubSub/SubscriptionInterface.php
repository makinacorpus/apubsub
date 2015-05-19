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
     * @return scalar
     *   Subsriber identifier Can be any scalar type, depending on how the
     *   channel handles its subscribers
     */
    public function getId();

    /**
     * Get creation date
     *
     * @return \DateTime
     */
    public function getCreationDate();

    /**
     * Does this subscription is still active
     *
     * @return bool
     */
    public function isActive();

    /**
     * Get the date when this subscription started
     *
     * @return \DateTime
     */
    public function getStartDate();

    /**
     * Get the date when this subscription stopped
     *
     * @return \DateTime
     *
     * @throws \RuntimeException
     *   If subscription is still active
     */
    public function getStopDate();

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
