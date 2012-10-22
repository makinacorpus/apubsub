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

    public function addChannel(D7SimpleChannel $channel)
    {
    }

    public function getSubscription($id)
    {
    }

    public function addSubscription(D7SimpleSubscription $subscription)
    {
    }

    public function flush()
    {
    }
}
