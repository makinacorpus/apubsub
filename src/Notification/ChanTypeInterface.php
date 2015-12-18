<?php

namespace MakinaCorpus\APubSub\Notification;

/**
 * Notification formatter
 */
interface ChanTypeInterface extends RegistryItemInterface
{
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
