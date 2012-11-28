<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\Drupal7\Helper\D7ChannelList;
use APubSub\Backend\Drupal7\Helper\D7SubscriberList;
use APubSub\Backend\Drupal7\Helper\D7SubscriptionList;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\PubSubInterface;

/**
 * Drupal 7 backend implementation
 */
class D7PubSub extends AbstractD7Object implements PubSubInterface
{
    /**
     * Default constructor
     *
     * @param DatabaseConnection $dbConnection Drupal database connexion
     * @param array|Traversable $options       Options
     */
    public function __construct(\DatabaseConnection $dbConnection, $options = null)
    {
        $this->context = new D7Context($dbConnection, $this, $options);
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
            ->dbConnection
            ->query("SELECT * FROM {apb_chan} WHERE name = :name", array(':name' => $id))
            ->fetchObject();

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        $channel = new D7Channel($this->context, $record->name,
            (int)$record->id, (int)$record->created);

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

        // Short-circuiting thus avoiding useless SQL queries
        if (empty($missingIdList)) {
            return $ret;
        }

        $recordList = $this
            ->context
            ->dbConnection
            ->select('apb_chan', 'c')
            ->fields('c')
            ->condition('name', $missingIdList, 'IN')
            ->execute()
            // Fetch all is mandatory, else we cannot count
            ->fetchAll();

        if (count($recordList) !== count($missingIdList)) {
            throw new ChannelDoesNotExistException();
        }

        foreach ($recordList as $record) {
            $channel = new D7Channel($this->context,
                $record->name, (int)$record->id, (int)$record->created);

            $ret[$record->name] = $channel;

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
                $missingIdList[] = $id;
            }
        }

        // Short-circuiting thus avoiding useless SQL queries
        if (empty($missingIdList)) {
            return $ret;
        }

        $recordList = $this
            ->context
            ->dbConnection
            ->select('apb_chan', 'c')
            ->fields('c')
            ->condition('id', $missingIdList, 'IN')
            ->execute()
            // Fetch all is mandatory, else we cannot count
            ->fetchAll();

        if (count($recordList) !== count($missingIdList)) {
            throw new ChannelDoesNotExistException();
        }

        foreach ($recordList as $record) {
            $channel = new D7Channel($this->context,
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
     * @return \APubSub\Backend\Drupal7\D7Channel
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
            ->dbConnection
            ->query("SELECT * FROM {apb_chan} WHERE id = :id", array(':id' => $id))
            ->fetchObject();

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        $channel = new D7Channel($this->context, $record->name, (int)$record->id, (int)$record->created);

        $this->context->cache->addChannel($channel);

        return $channel;
    }

    /**
     * Internal helper for pure performances purpose
     *
     * @param int $idList                       Channel database identifiers
     *
     * @return \APubSub\Backend\Drupal7\D7Channel
     *                                          Loaded instance
     *
     * @throws \APubSub\Error\ChannelDoesNotExistException
     *                                          If channel does not exist in
     *                                          database
     */
    public function getChannelsByDatabaseIds($idList)
    {
        $ret = array();
        $missingIdList = array();

        // First populate cache from what we have
        foreach ($idList as $id) {
            if ($channel = $this->context->cache->getChannelByDatabaseId($id)) {
                $ret[$id] = $channel;
            } else {
                $missingIdList[] = $id;
            }
        }

        // Short-circuiting thus avoiding useless SQL queries
        if (empty($missingIdList)) {
            return $ret;
        }

        $recordList = $this
            ->context
            ->dbConnection
            ->select('apb_chan', 'c')
            ->fields('c')
            ->condition('id', $missingIdList, 'IN')
            ->execute()
            // Fetch all is mandatory, else we cannot count
            ->fetchAll();

        if (count($recordList) !== count($missingIdList)) {
            throw new ChannelDoesNotExistException();
        }

        foreach ($recordList as $record) {
            $id = (int)$record->id;
            $channel = new D7Channel($this->context, $record->name, $id, (int)$record->created);;

            $ret[$id] = $channel;

            $this->context->cache->addChannel($channel);
        }

        array_multisort($idList, $ret);

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannel()
     */
    public function createChannel($id, $ignoreErrors = false)
    {
        $channel = null;
        $created = time();
        $dbId    = null;
        $cx      = $this->context->dbConnection;
        $tx      = $cx->startTransaction();

        try {
            // Do not ever use cache here, we cannot afford to try to create
            // a channel that have been deleted by another thread
            $exists = $cx
                ->query("SELECT 1 FROM {apb_chan} c WHERE c.name = :name", array(
                    ':name' => $id,
                ))
                ->fetchField();

            if ($exists) {
                if ($ignoreErrors) {
                    $channel = $this->getChannel($id);
                } else {
                    throw new ChannelAlreadyExistsException();
                }
            } else {
                $cx
                    ->insert('apb_chan')
                    ->fields(array(
                        'name' => $id,
                        'created' => $created,
                    ))
                    ->execute();

                $dbId = (int)$cx->lastInsertId();
                $channel = new D7Channel($this->context, $id, $dbId, $created);

                // In case of success, populate internal static cache
                $this->context->cache->addChannel($channel);
            }

            unset($tx); // Explicit commit
        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return $channel;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::createChannels()
     */
    public function createChannels($idList, $ignoreErrors = false)
    {
        if (empty($idList)) {
            return $ret;
        }

        $ret      = array();
        $created  = time();
        $dbId     = null;
        $cx       = $this->context->dbConnection;
        $tx       = $cx->startTransaction();

        try {
            // Do not ever use cache here, we cannot afford to try to create
            // a channel that have been deleted by another thread
            $existingList = $cx
                ->select('apb_chan', 'c')
                ->fields('c', array('name'))
                ->condition('c.name', $idList, 'IN')
                ->execute()
                ->fetchCol();

            if ($existingList && !$ignoreErrors) {
                throw new ChannelAlreadyExistsException();
            }

            $query = $cx
                ->insert('apb_chan')
                ->fields(array(
                    'name',
                    'created',
                ));

            // Create only the non existing one, in one query
            foreach (array_diff($idList, $existingList) as $id) {
                $query->values(array(
                    $id,
                    $created,
                ));
            }

            $query->execute();

            // We can get back an id with the last insert id, but if the
            // user created 10 chans, better do 2 SQL queries than 10!
            // This is also the smart part of this function: we will load
            // all channels at once instead of doing a first query for
            // existing and a second for newly created ones, thus allowing
            // us to keep the list ordered implicitely as long as the get
            // method keeps it ordered too
            $ret = $this->getChannels($idList);

            unset($tx); // Explicit commit
        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::deleteChannel()
     */
    public function deleteChannel($id)
    {
        $dbId = null;
        $cx   = $this->context->dbConnection;
        $tx   = $cx->startTransaction();

        try {
            // FIXME: SELECT FOR UPDATE here in all tables

            $dbId = (int)$cx
                ->query("SELECT id FROM {apb_chan} WHERE name = :name", array(
                    ':name' => $id,
                ))
                ->fetchField();

            if (!$dbId) {
                throw new ChannelDoesNotExistException();
            }

            $args = array(':dbId' => $dbId);

            // Queue is not the most optimized query but is necessary
            // FIXME: Joining with apb_sub instead might be more efficient
            $cx->query("
                DELETE FROM {apb_queue}
                    WHERE msg_id IN (
                        SELECT m.id FROM {apb_msg} m
                            WHERE m.chan_id = :dbId
                    )
                ", $args);

            // Delete subscriptions and messages
            $cx->query("DELETE FROM {apb_msg} WHERE chan_id = :dbId", $args);
            $cx->query("DELETE FROM {apb_sub} WHERE chan_id = :dbId", $args);

            // Finally the last strike
            $cx->query("DELETE FROM {apb_chan} WHERE id = :dbId", $args);

            unset($tx); // Explicit commit

            // FIXME: Deleting a channel actually does a lot of bad things
            // including deleting subscriptions, removing messages and
            // invalidating subscribers: do not allow anything to remain in
            // static cache, checking all dependencies one by one would be
            // uselessly complex code considering that deleting a channel
            // is *not* something you are going to do in every day
            $this->context->cache->flush();

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getChannelListHelper()
     */
    public function getChannelListHelper()
    {
        return new D7ChannelList($this->context);
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
            ->dbConnection
            ->query("SELECT * FROM {apb_sub} WHERE id = :id", array(
                ':id' => $id,
            ))
            ->fetchObject();

        // FIXME: Useless chan lookup?
        if (!$record || !($channel = $this->getChannelByDatabaseId($record->chan_id))) {
            // Subscription may exist, but channel does not anymore case in
            // which we consider it should be dropped
            throw new SubscriptionDoesNotExistException();
        }

        $subscription = new D7Subscription($this->context,
            (int)$record->chan_id, (int)$record->id,
            (int)$record->created, (int)$record->activated,
            (int)$record->deactivated, (bool)$record->status);

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
                $missingIdList[] = $id;
            }
        }

        // Short-circuiting thus avoiding useless SQL queries
        if (empty($missingIdList)) {
            return $ret;
        }

        $recordList = $this
            ->context
            ->dbConnection
            ->select('apb_sub', 's')
            ->fields('s')
            ->condition('id', $missingIdList, 'IN')
            ->execute()
            // Fetch all is mandatory, else we cannot count
            ->fetchAll();

        if (count($recordList) !== count($missingIdList)) {
            throw new SubscriptionDoesNotExistException();
        }

        foreach ($recordList as $record) {
            $id = (int)$record->id;
            $subscription = new D7Subscription($this->context,
                (int)$record->chan_id, $id,
                (int)$record->created, (int)$record->activated,
                (int)$record->deactivated, (bool)$record->status);

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
        $cx = $this->context->dbConnection;
        $tx = $cx->startTransaction();

        try {
            $args = array(':id' => $id);

            // FIXME: SELECT FOR UPDATE here in all tables

            $exists = (bool)$cx
                // SELECT 1 would have been better, but as always Drupal is
                // incredibly stupid and does not allow us to write the SQL
                // we really want to
                ->query("SELECT id FROM {apb_sub} WHERE id = :id", $args)
                ->fetchField();

            if (!$exists) {
                // See comment in createChannel() method
                $tx->rollback();

                throw new ChannelDoesNotExistException();
            }

            // Clean subscribers and queue then delete subscription
            $cx->query("DELETE FROM {apb_sub_map} WHERE sub_id = :id", $args);
            $cx->query("DELETE FROM {apb_queue} WHERE sub_id = :id", $args);
            $cx->query("DELETE FROM {apb_sub} WHERE id = :id", $args);

            unset($tx); // Explicit commit

            $this->context->cache->removeSubscription($id);

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }
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
     * @see \APubSub\PubSubInterface::getSubscriptionListHelper()
     */
    public function getSubscriptionListHelper()
    {
        return new D7SubscriptionList($this->context);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscriber()
     */
    public function getSubscriber($id)
    {
        // In this implementation all writes will be delayed on real operations
        return new D7Subscriber($this->context, $id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\PubSubInterface::getSubscriberListHelper()
     */
    public function getSubscriberListHelper()
    {
        return new D7SubscriberList($this->context);
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
        // Ensure queue max size
        $this->cleanUpMessageQueue();

        // Ensure messages max lifetime
        $this->cleanUpMessageLifeTime();
    }

    /**
     * Use context configuration and wipe out sent messages from queue if
     * the queue limit is reached
     */
    public function cleanUpMessageQueue()
    {
        // Drop all messages for inactive subscriptions
        $min = $this
            ->context
            ->dbConnection
            ->query("
                  DELETE FROM {apb_queue}
                      WHERE sub_id IN (
                          SELECT id FROM {apb_sub}
                              WHERE status = 0
                      )
                  ");

        // Limit queue size if configured for
        if ($this->context->queueGlobalLimit) {

            $min = $this
                ->context
                ->dbConnection
                ->query("
                    SELECT msg_id FROM {apb_queue}
                        ORDER BY msg_id DESC
                        OFFSET :max LIMIT 1
                    ", array(
                        ':max' => $this->context->queueGlobalLimit,
                    ))
                ->fetchField();

            if ($min) {
                // If the same message exists in many queues, we will never
                // reach the exact global limit: deleting all messages with
                // a lower id ensures that we may be close enought to this
                // limit
                $this
                    ->context
                    ->dbConnection
                    ->query("DELETE FROM {apb_queue} WHERE msg_id < :min", array(
                        ':min' => $min,
                    ));
            }
        }
    }

    /**
     * Use context configuration and remove outdate message according to
     * maximum message lifetime
     */
    public function cleanUpMessageLifeTime()
    {
        if ($this->context->messageMaxLifetime) {

            // Delete messages
            $this
                ->context
                ->dbConnection
                ->query("DELETE FROM {apb_msg} WHERE created < :time", array(
                    ':time' => time() - $this->context->messageMaxLifetime,
                ));

            // Delete in queue
            $this
                ->context
                ->dbConnection
                ->query("
                    DELETE FROM {apb_queue}
                        WHERE msg_id NOT IN (
                            SELECT id FROM {apb_msg}
                        )
                    ");
        }
    }

    public function getAnalysis()
    {
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
    }
}
