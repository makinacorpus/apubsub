<?php

namespace APubSub\Backend\Drupal7\Helper;

class NullCache
{
    public function getChannelByDatabaseId($id)
    {
    }

    public function getChannel($id)
    {
    }

    public function addChannel(D7Channel $chan)
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
