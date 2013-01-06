<?php

namespace APubSub\Backend\Drupal7;

class InternalCache
{
    /**
     * Channel cache (database id is the key)
     *
     * @var array
     */
    private $channelCacheByDbId = array();

    /**
     * Channel string id to database id mapping
     *
     * @var array
     */
    private $channelIdMapping = array();

    /**
     * Subscriptions cache (id is the key)
     *
     * @var array
     */
    private $subscriptionCache = array();


    public function getChannelByDatabaseId($id)
    {
        if (isset($this->channelCacheByDbId[$id])) {
            return $this->channelCacheByDbId[$id];
        }
    }

    public function getChannel($id)
    {
        if (isset($this->channelIdMapping[$id])) {
            return $this->getChannelByDatabaseId($id);
        }
    }

    public function addChannel(D7Channel $channel)
    {
        $dbId = $channel->getDatabaseId();
        $this->channelCacheByDbId[$dbId] = $channel;
        $this->channelIdMapping[$channel->getId()] = $dbId;
    }

    public function removeChannel($id)
    {
        if (isset($this->channelIdMapping[$id])) {
            $dbId = $this->channelIdMapping[$id];
            unset(
                $this->channelCacheByDbId[$dbId],
                $this->channelIdMapping[$id]
            );
        }
    }

    public function getSubscription($id)
    {
        if (isset($this->subscriptionCache[$id])) {
            return $this->subscriptionCache[$id];
        }
    }

    public function addSubscription(D7Subscription $subscription)
    {
        return $this->subscriptionCache[$subscription->getId()] = $subscription;
    }

    public function removeSubscription($id)
    {
        unset($this->subscriptionCache[$id]);
    }

    public function flush()
    {
        $this->channelCacheByDbId = array();
        $this->channelIdMapping = array();
        $this->subscriptionCache = array();
    }
}
