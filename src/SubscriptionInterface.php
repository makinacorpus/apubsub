<?php

namespace MakinaCorpus\APubSub;

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
     * Get the date when this subscription has been marked
     *
     * This date is never updated by this API but might be by external business
     * code for business purposes
     *
     * @return \DateTime
     *   May be null
     */
    public function getAccessDate();

    /**
     * Does this subscription belongs to a subscriber
     *
     * @return boolean
     */
    public function hasSubscriber();

    /**
     * Get subscriber identifier
     *
     * @return string
     */
    public function getSubscriberId();

    /**
     * Get loaded subscriber
     *
     * @return SubscriberInterface
     *
     * @throws \RuntimeException
     *   If there is no subscriber for this subscription
     */
    public function getSubscriber();

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
