<?php

namespace APubSub\Notification;

/**
 * Notification formatter
 */
interface ChanTypeInterface
{
    /**
     * Get internal type
     *
     * @return string
     */
    public function getType();

    /**
     * Get type description
     *
     * @return string Human readable string
     */
    public function getDescription();

    /**
     * Is visible for the end user
     *
     * @return boolean True if the end user can manipulate subscriptions to
     *                 these notifications
     */
    public function isVisible();

    /**
     * Get subscription label corresponding to given id
     *
     * For example, if this instance is for formatting document oriented
     * notifications, you can return something like "Document $id modifications"
     *
     * @param scalar $id
     */
    public function getSubscriptionLabel($id);
}
