<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\DefaultMessage;

class MemoryMessage extends DefaultMessage
{
    /**
     * Subscribed messages
     *
     * @var array
     */
    private $subscriptionIdList = array();

    /**
     * Set subscription ids for which this message is targeted
     *
     * @param array $subscriptionIdList Target subscriptions
     */
    public function setSubscriptionIds(array $subscriptionIdList)
    {
        $this->subscriptionIdList = $subscriptionIdList;
    }

    /**
     * Does this message still have been consumed by all subscriptions
     *
     * @return bool If the subscription list is empty
     */
    public function isConsumed()
    {
        return empty($this->subscriptionIdList);
    }

    /**
     * Remove subscription identifiers
     *
     * @param array $subscriptionIdList Subscription identifiers
     */
    public function removeSubscriptionIds(array $subscriptionIdList)
    {
        $this->subscriptionIdList = array_diff(
            $this->subscriptionIdList, $subscriptionIdList);
    }

    /**
     * Tell if the current message has at least one of the given subscription
     * as target
     *
     * @param array $subscriptionIdList Subscriptions identifiers
     */
    public function hasSubscribersIn(array $subscriptionIdList)
    {
        foreach ($this->subscriptionIdList as $id) {
            foreach ($subscriptionIdList as $targetId) {
                if ($targetId === $id) {
                    return true;
                }
            }
        }

        return false;
    }
}
