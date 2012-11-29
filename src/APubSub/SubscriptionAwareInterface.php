<?php

namespace APubSub;

interface SubscriptionAwareInterface
{
    /**
     * Get subscription identifier
     *
     * Use this method rather than getSubscription() whenever possible, in
     * order to avoid backends lookup in most implementations
     *
     * @return mixed Subscription identiifer
     */
    public function getSubscriptionId();

    /**
     * Get channel
     *
     * @return \APubSub\SubscriptionInterface
    */
    public function getSubscription();
}
