<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultSubscription;
use APubSub\SubscriptionInterface;

/**
 * Drupal 7 simple subscription implementation
 *
 * @todo Remove this class if possible
 */
class D7Subscription extends DefaultSubscription implements
    SubscriptionInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::deactivate()
     */
    public function deactivate()
    {
        $deactivated = time();

        $this
            ->context
            ->dbConnection
            ->query("UPDATE {apb_sub} SET status = 0, deactivated = :deactivated WHERE id = :id", array(
                ':deactivated' => $deactivated,
                ':id'          => $this->getId(),
            ));

        $this->active = false;
        $this->deactivatedTime = $deactivated;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriptionInterface::activate()
     */
    public function activate()
    {
        $activated = time();

        $this
            ->context
            ->dbConnection
            ->query("UPDATE {apb_sub} SET status = 1, activated = :activated WHERE id = :id", array(
                ':activated' => $activated,
                ':id'        => $this->getId(),
            ));

        $this->active = true;
        $this->activatedTime = $activated;
    }
}
