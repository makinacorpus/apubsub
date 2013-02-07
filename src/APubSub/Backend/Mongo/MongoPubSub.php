<?php

namespace APubSub\Backend\Mongo;

use APubSub\Backend\AbstractObject;
//use APubSub\Backend\Mongo\Cursor\MongoChannelCursor;
//use APubSub\Backend\Mongo\Cursor\MongoSubscriberCursor;
//use APubSub\Backend\Mongo\Cursor\MongoSubscriptionCursor;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\PubSubInterface;

/**
 * Mongo DB (using Mongo PHP extension) backend
 *
 * This backend will work with only three collections:
 *   - channels
 *   - subscriptions
 *   - queue
 *
 * The one you'd want to shard will be the "queue" collection (name depend on
 * your runtime configuration)
 *
 * Channel documents will contain:
 *   - _id     : \MongoId
 *   - name    : string
 *   - created : int
 *
 * Subscription documents will contain:
 *   - _id         : \MongoId
 *   - chan_id     : \MongoId
 *   - status      : boolean
 *   - chan_name   : string
 *   - subscriber  : string (null)
 *   - deactivated : int (null)
 *   - activated   : int (null)
 *   - created     : int (null)
 *
 * Queue documents will contain:
 *   - msg_id    : \MongoId
 *   - sub_id    : \MongoId
 *   - chan_id   : \MongoId
 *   - chan_name : string
 *   - created   : int
 *   - contents  : binary
 */
class MongoPubSub extends AbstractObject implements PubSubInterface
{
    /**
     * Default constructor
     *
     * @param \MongoDB $dbConnection     Mongo database connexion
     * @param array|Traversable $options Options
     */
    public function __construct(\MongoDB $dbConnection, $options = null)
    {
        $this->context = new MongoContext($dbConnection, $this, $options);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::setOptions()
     */
    public function setOptions(array $options)
    {
        $this->context->setOptions($options);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannel()
     */
    public function getChannel($id)
    {
        if ($channel = $this->context->cache->getChannel($id)) {
            return $channel;
        }

        $record = $this
            ->context
            ->chanCollection
            ->findOne(array(
                'name' => $id,
            ));

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        $channel = new MongoChannel($this->context, $record['name'],
            (string)$record['_id'], (int)$record['created']);

        $this->context->cache->addChannel($channel);

        return $channel;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannels()
     */
    public function getChannels(array $idList)
    {
        $ret           = array();
        $missingIdList = array();

        // First populate cache from what we have
        foreach ($idList as $id) {
            if ($channel = $this->context->cache->getChannel($id)) {
                $ret[$id] = $channel;
            } else {
                $missingIdList[] = $id;
            }
        }

        // Short-circuiting thus avoiding useless queries
        if (empty($missingIdList)) {
            return $ret;
        }

        $recordList = $this
            ->chanCollection
            ->find(array(
                'name' => array(
                    '$in' => $missingIdList,
                ),
            ));

        if (count($recordList) !== count($missingIdList)) {
            throw new ChannelDoesNotExistException();
        }

        foreach ($recordList as $record) {
            $channel = new MongoChannel($this->context,
                $record['name'], (string)$record['_id'], (int)$record['created']);

            $ret[$record['name']] = $channel;

            $this->context->cache->addChannel($channel);
        }

        array_multisort($idList, $ret);

        return $ret;
    }

    /**
     * Get channels by database identifiers
     *
     * Method signature is the same as getChannels()
     *
     * @param array $idList List of database identifiers
     */
    public function getChannelsByDatabaseId(array $idList)
    {
        $ret           = array();
        $missingIdList = array();

        // First populate cache from what we have
        foreach ($idList as $id) {
            if ($channel = $this->context->cache->getChannelByDatabaseId($id)) {
                $ret[$id] = $channel;
            } else {
                $missingIdList[] = new \MongoId($id);
            }
        }

        // Short-circuiting thus avoiding useless SQL queries
        if (empty($missingIdList)) {
            return $ret;
        }

        $recordList = $this
            ->chanCollection
            ->find(array(
                '_i' => array(
                    '$in' => $missingIdList,
                ),
            ));

        if (count($recordList) !== count($missingIdList)) {
            throw new ChannelDoesNotExistException();
        }

        foreach ($recordList as $record) {
            $channel = new MongoChannel($this->context,
                $record->name, (int)$record->id, (int)$record->created);

            $ret[$record->name] = $channel;

            $this->context->cache->addChannel($channel);
        }

        array_multisort($idList, $ret);

        return $ret;
    }

    /**
     * Internal helper for pure performances purpose
     *
     * @param int $id                           Channel database identifier
     *
     * @return \APubSub\Backend\Mongo\MongoChannel
     *                                          Loaded instance
     *
     * @throws \APubSub\Error\ChannelDoesNotExistException
     *                                          If channel does not exist in
     *                                          database
     */
    public function getChannelByDatabaseId($id)
    {
        if ($channel = $this->context->cache->getChannel($id)) {
            return $channel;
        }

        $record = $this
            ->context
            ->chanCollection
            ->findOne(array(
                '_id' => new \MongoId($id),
            ));

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        $channel = new MongoChannel($this->context, $record['name'],
            (string)$record['_id'], (int)$record['created']);

        $this->context->cache->addChannel($channel);

        return $channel;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannel()
     */
    public function createChannel($id, $ignoreErrors = false)
    {
        // @todo De-uglify
        try {
            $channel = $this->getChannel($id);

            if ($ignoreErrors) {
                return $channel;
            } else {
                throw new ChannelAlreadyExistsException();
            }
        } catch (ChannelDoesNotExistException $e) {}

        $created = time();

        $ret = $this
            ->context
            ->chanCollection
            ->save(
                array(
                    'name'    => $id,
                    'created' => $created,
                ),
                array(
                    'safe' => true,
                )
            );

        // Mongo does not seem to allow returning the generated _id on save or
        // insert operations, we must load using the user name to find it back
        // in the new instance
        return $this->getChannel($id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannels()
     */
    public function createChannels($idList, $ignoreErrors = false)
    {
        $ret = array();

        foreach ($idList as $id) {
            $ret[] = $this->createChannel($id, $ignoreErrors);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        try {
            $channel = $this->getChannel($id);
            $dbId    = new \MongoId($channel->getDatabaseId());

            $this
                ->context
                ->subCollection
                ->remove(array(
                    'chan_id' => $dbId,
                ));

            $this
                ->context
                ->queueCollection
                ->remove(array(
                    'chan_id' => $dbId,
                ));

            $this
                ->context
                ->chanCollection
                ->remove(array(
                    '_id' => $dbId,
                ));

        } catch (ChannelDoesNotExistException $e) {
            // Nothing to do I guess.
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscription()
     */
    public function getSubscription($id)
    {
        if ($subscription = $this->context->cache->getSubscription($id)) {
            return $subscription;
        }

        $record = $this
            ->context
            ->subCollection
            ->findOne(array(
                '_id' => new \MongoId($id),
            ));

        $subscription = new MongoSubscription($this->context,
            (string)$record['_id'],
            (int)$record['chan_id'], (string)$record['chan_name'],
            (int)$record['created'], (int)$record['activated'],
            (int)$record['deactivated'], (bool)$record['status']);

        $this->context->cache->addSubscription($subscription);

        return $subscription;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscriptions()
     */
    public function getSubscriptions($idList)
    {
        $ret = array();
        $missingIdList = array();

        // First populate cache from what we have
        foreach ($idList as $id) {
            if ($subscription = $this->context->cache->getSubscription($id)) {
                $ret[$id] = $subscription;
            } else {
                $missingIdList[] = new \MongoId($id);
            }
        }

        // Short-circuiting thus avoiding useless SQL queries
        if (empty($missingIdList)) {
            return $ret;
        }

        $record = $this
            ->context
            ->subCollection
            ->find(array(
                '_id' => array(
                    '$in' => $missingIdList,
                ),
            ));

        if (count($recordList) !== count($missingIdList)) {
            throw new SubscriptionDoesNotExistException();
        }

        foreach ($recordList as $record) {
            $id = (string)$record['_id'];

            $subscription = new MongoSubscription($this->context,
                (string)$record['_id'],
                (int)$record['chan_id'], (string)$record['chan_name'],
                (int)$record['created'], (int)$record['activated'],
                (int)$record['deactivated'], (bool)$record['status']);

            $ret[$id] = $subscription;

            $this->context->cache->addSubscription($subscription);
        }

        array_multisort($idList, $ret);

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscription()
     */
    public function deleteSubscription($id)
    {
        $id = new \MongoId($id);

        $this
            ->context
            ->queueCollection
            ->remove(array(
                'sub_id' => $id,
            ));

        $this
            ->context
            ->subCollection
            ->remove(array(
                '_id' => $id,
            ));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteSubscriptions()
     */
    public function deleteSubscriptions($idList)
    {
        // This doesn't sound revelant to optimize this method, subscriptions
        // should not be to transcient, they have not been meant to anyway:
        // deleting subscriptions will be a costy operation whatever the effort
        // to make in deleting them faster
        foreach ($idList as $id) {
            $this->deleteSubscription($id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscriber()
     */
    public function getSubscriber($id)
    {
        // In this implementation all writes will be delayed on real operations
        return new MongoSubscriber($this->context, $id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::flushCaches()
     */
    public function flushCaches()
    {
        $this->context->flushCaches();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::garbageCollection()
     */
    public function garbageCollection()
    {
        /*
        // Ensure queue max size
        $this->cleanUpMessageQueue();

        // Ensure messages max lifetime
        $this->cleanUpMessageLifeTime();
         */
    }

    public function getAnalysis()
    {
        /*
        $cx = $this->context->dbConnection;

        $chanCount       = (int)$cx->query("SELECT COUNT(*) FROM {apb_chan}")->fetchField();
        $msgCount        = (int)$cx->query("SELECT COUNT(*) FROM {apb_msg}")->fetchField();
        $subCount        = (int)$cx->query("SELECT COUNT(*) FROM {apb_sub}")->fetchField();
        $subscriberCount = (int)$cx->query("SELECT COUNT(name) FROM {apb_sub_map} GROUP BY name")->fetchField();
        $queueSize       = (int)$cx->query("SELECT COUNT(*) FROM {apb_queue}")->fetchField();

        return array(
            "Channel count"       => $chanCount,
            "Message count"       => $msgCount,
            "Subscriptions count" => $subCount,
            "Subscribers count"   => $subscriberCount,
            "Total queue size"    => $queueSize,
        );
         */
    }
}
