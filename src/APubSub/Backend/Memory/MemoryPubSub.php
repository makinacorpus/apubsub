<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\AbstractBackend;
use APubSub\Backend\ArrayCursor;
use APubSub\CursorInterface;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\Backend\DefaultSubscriber;

/**
 * Array based implementation for unit testing: do not use in production
 */
class MemoryPubSub extends AbstractBackend
{
    /**
     * @var MemoryContext
     */
    protected $context;

    /**
     * Sort helper for messages
     *
     * @param MemorySubscriber $a Too lazy to comment
     * @param array $conditions   Too lazy to comment
     *
     * @return bool               Too lazy to comment
     *
    public static function filterSubscribers(MemorySubscriber $a, array $conditions)
    {
        foreach ($conditions as $key => $value) {

            $value = null;

            switch ($key) {

                case CursorInterface::FIELD_SUBER_ACCESS:
                    $value = $a->isUnread();
                    break;

                case CursorInterface::FIELD_SUBER_NAME:
                    $value = $a->isUnread();
                    break;
            }

            if (null === $key && $value !== null || $key != $value) {
                return false;
            }
        }

        return true;
    }
     */

    public function __construct()
    {
        $this->context = new MemoryContext($this);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::setOptions()
     */
    public function setOptions(array $options)
    {
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getChannel()
     */
    public function getChannel($id)
    {
        if (!isset($this->context->chans[$id])) {
            throw new ChannelDoesNotExistException();
        }

        return $this->context->chans[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getChannels()
     */
    public function getChannels(array $idList)
    {
        $ret = array();

        foreach ($idList as $chanId) {
            $ret[] = $this->getChannel($chanId);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::createChannel()
     */
    public function createChannel($id, $ignoreErrors = false)
    {
        if (isset($this->context->chans[$id])) {
            if ($ignoreErrors) {
                return $this->getChannel($id);
            } else {
                throw new ChannelAlreadyExistsException();
            }
        } else {
            return $this->context->chans[$id] = new MemoryChannel($this->context, $id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::createChannels()
     */
    public function createChannels($idList, $ignoreErrors = false)
    {
        $ret = array();

        if ($ignoreErrors) {
            foreach ($idList as $id) {
                $ret[] = $this->createChannel($id, true);
            }
        } else {
            $existing = array_intersect_key(array_flip($idList), $this->context->chans);

            if (empty($existing)) {
                foreach ($idList as $id) {
                    // They are not supposed to exist
                    $ret[] = $this->createChannel($id, true);
                }
            } else {
                throw new ChannelAlreadyExistsException();
            }
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        $chan = $this->getChannel($id);

        foreach ($this->context->subscriptions as $index => $subscription) {
            if ($subscription->getChannel()->getId() === $id) {
                unset($this->context->subscriptions[$index]);
            }
        }
        $this->context->subscriptions = array_filter($this->context->subscriptions);

        unset($this->context->chans[$id]);
        unset($this->context->chanMessages[$id]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getSubscription()
     */
    public function getSubscription($id)
    {
        if (!isset($this->context->subscriptions[$id])) {
            throw new SubscriptionDoesNotExistException();
        }

        return $this->context->subscriptions[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getSubscriptions()
     */
    public function getSubscriptions($idList)
    {
        $ret = array();

        foreach ($idList as $id) {
            $ret[] = $this->getSubscription($id);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::deleteSubscription()
     */
    public function deleteSubscription($id)
    {
        $this->getSubscription($id);

        unset($this->context->subscriptions[$id]);
        unset($this->context->subscriptionMessages[$id]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::deleteSubscriptions()
     */
    public function deleteSubscriptions($idList)
    {
        foreach ($idList as $id) {
            $this->deleteSubscription($id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::fetchSubscribers()
     */
    public function fetchSubscribers(array $conditions = null)
    {
        throw new \Exception("Not implemented yet");

        $ret = $this->context->subscribers;

        if ($conditions) {
            $ret = array_filter($ret, function ($a) use ($conditions) {
                return MemoryPubSub::filterSubscribers($a, $conditions);
            });
        }

        $sorter = new MemorySubscriberSorter();

        return new ArrayCursor($this->context, $ret, $sorter->getAvailableSorts(), $sorter);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getSubscriber()
     */
    public function getSubscriber($id)
    {
        if (!isset($this->context->subscribers[$id])) {
            $this->context->subscribers[$id] = new DefaultSubscriber($id, $this->context);
        }

        return $this->context->subscribers[$id];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::subscribe()
     */
    public function subscribe($chanId, $subscriberId = null)
    {
        if ($subscriberId) {
            $subscriber = $this->getSubscriber($subscriberId);

            if ($subscriber->hasSubscriptionFor($chanId)) {
                return $subscriber->getSubscriptionFor($chanId);
            }
        }

        // FIXME: When this is called with the $subscriberId set outside of
        // of the DefaultSubscriber::subscribe() method, the subscription
        // will not be set into the subscriber
        return new MemorySubscription($context, $chanId);
    }

    /**
     * Send a single message to one or more channels
     *
     * @param string|string[] $chanId List of channels or single channel to send
     *                                the message too
     * @param string $type            Message type
     * @param int $level              Arbitrary business level
     * @param int $sendTime           If set the creation/send timestamp will be
     *                                forced to the given value
     */
    public function send($chanId, $contents, $type = null, $level = 0, $sendTime = null)
    {
        // FIXME: handle multiple channels

        throw new \Exception("Not implemented yet");

        if (null === $sendTime) {
            $sendTime = time();
        }

        $msgId = $this->context->getNextMessageIdentifier();

        $message = new MemoryMessage(
            $this->context,
            $chanId,
            null,
            $contents,
            $msgId,
            $sendTime);

        if (!is_array($chanId)) {
            $chanId = array($chanId);
        }

        /*
         * FIXME: Redo the whole cache handling of memory backend
         *
        foreach ($chanId as $id) {
            $this->context->
        }
         */
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::flushCaches()
     */
    public function flushCaches()
    {
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::garbageCollection()
     */
    public function garbageCollection()
    {
    }

    public function getAnalysis()
    {
        // This is a pure implementation sample, there is no way you would ever
        // try to run this on a production environment
        return array(
            "Number of chans" => count($this->context->chans),
            "Number of subscribers" => count($this->context->subscribers),
            "Number of subscriptions" => count($this->context->subscriptions),
        );
    }
}
