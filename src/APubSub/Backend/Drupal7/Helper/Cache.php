<?php

namespace APubSub\Backend\Drupal7\Helper;

use APubSub\Backend\Drupal7\D7Channel;
use APubSub\Backend\Drupal7\D7Subscription;

class Cache
{
    /**
     * Channel cache (database id is the key)
     *
     * @var array
     */
    private $chanCacheByDbId = array();

    /**
     * Channel string id to database id mapping
     *
     * @var array
     */
    private $chanIdMapping = array();

    /**
     * Subscriptions cache (id is the key)
     *
     * @var array
     */
    private $subscriptionCache = array();


    public function getChannelByDatabaseId($id)
    {
        if (isset($this->chanCacheByDbId[$id])) {
            return $this->chanCacheByDbId[$id];
        }
    }

    public function getChannel($id)
    {
        if (isset($this->chanIdMapping[$id])) {
            return $this->getChannelByDatabaseId($id);
        }
    }

    public function addChannel(D7Channel $chan)
    {
        $dbId = $chan->getDatabaseId();
        $this->chanCacheByDbId[$dbId] = $chan;
        $this->chanIdMapping[$chan->getId()] = $dbId;
    }

    public function removeChannel($id)
    {
        if (isset($this->chanIdMapping[$id])) {
            $dbId = $this->chanIdMapping[$id];
            unset(
                $this->chanCacheByDbId[$dbId],
                $this->chanIdMapping[$id]
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
        $this->chanCacheByDbId = array();
        $this->chanIdMapping = array();
        $this->subscriptionCache = array();
    }
}
