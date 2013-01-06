<?php

namespace APubSub\Stat;

use APubSub\ChannelAwareInterface;
use APubSub\ObjectInterface;

interface ChannelStatInterface extends ObjectInterface, ChannelAwareInterface
{
    /**
     * Get channel
     *
     * @return \APubSub\ChannelInterface The channel this stats are about
     */
    public function getChannel();

    /**
     * Are numbers reliable
     *
     * Backends for performance or technical reasons may not return reliable
     * numbers but approximative or even totally stupid figures instead
     *
     * @return bool True if counts are exact false otherwise
     */
    public function isReliable();

    /**
     * Get actual queue size (unread messages)
     *
     * @return int Current number of messages in queue
     */
    public function getQueueSize();

    /**
     * Get message count that have been sent since the creation of time
     *
     * @return int Total count of messages sent
     */
    public function getTotalMessageCount();

    /**
     * Get number of active subscriptions in this channel
     *
     * @return int Number of active subscriptions
     */
    public function getActiveSubscriptionCount();

    /**
     * Get the total number of subscriptions inactive included
     *
     * @return int Number of subscriptions
     */
    public function getTotalSubscriptionCount();
}
