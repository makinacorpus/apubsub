<?php

namespace APubSub\Backend\Mongo;

class NullInternalCache
{
    public function getChannelByDatabaseId($id)
    {
    }

    public function getChannel($id)
    {
    }

    public function addChannel(MongoChannel $channel)
    {
    }

    public function getSubscription($id)
    {
    }

    public function addSubscription(MongoSubscription $subscription)
    {
    }

    public function flush()
    {
    }
}
