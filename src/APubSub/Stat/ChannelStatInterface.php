<?php

namespace APubSub\Stat;

interface ChannelStatInterface
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
     * Backends for performance or technical reason may not return reliable
     * numbers but approximations or even totally stupid figures instead
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
