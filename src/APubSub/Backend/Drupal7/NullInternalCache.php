<?php

namespace APubSub\Backend\Drupal7;

class NullInternalCache
{
    public function getChannelByDatabaseId($id)
    {
    }

    public function getChannel($id)
    {
    }

    public function addChannel(D7Channel $channel)
    {
    }

    public function getSubscription($id)
    {
    }

    public function addSubscription(D7Subscription $subscription)
    {
    }

    public function flush()
    {
    }
}
