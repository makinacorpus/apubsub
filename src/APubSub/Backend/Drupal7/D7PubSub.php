<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractBackend;
use APubSub\Backend\DefaultMessage;
use APubSub\Backend\Drupal7\Cursor\D7ChannelCursor;
use APubSub\Backend\Drupal7\Cursor\D7MessageCursor;
use APubSub\Backend\Drupal7\Cursor\D7SubscriberCursor;
use APubSub\Backend\Drupal7\Cursor\D7SubscriptionCursor;
use APubSub\BackendInterface;
use APubSub\CursorInterface;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;

/**
 * Drupal 7 backend implementation
 */
class D7PubSub extends AbstractBackend implements BackendInterface
{
    /**
     * @var D7Context
     */
    protected $context;

    /**
     * Default constructor
     *
     * @param DatabaseConnection $dbConnection Drupal database connexion
     * @param array|Traversable $options       Options
     */
    public function __construct(\DatabaseConnection $dbConnection, $options = null)
    {
        parent::__construct(
            new D7Context(
                $dbConnection,
                $this,
                $options));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::fetch()
     */
    public function fetch(array $conditions = null)
    {
        $cursor = new D7MessageCursor($this->context);

        if (!empty($conditions)) {
            $cursor->applyConditions($conditions);
        }

        return $cursor;
    }

    /**
     * From the given conditions, store matching messages identifiers into
     * a temporary table and return the standalong SELECT query for getting
     * those identifiers.
     */
    private function getTempTableNameFrom(array $conditions)
    {
        $cx = $this
            ->context
            ->dbConnection;

        $cursor = $this->fetch($conditions);
        $cursor->setDistinct(false);
        $query  = $cursor->getQuery();

        // I am sorry but I have to be punished for I am writing this
        $selectFields = &$query->getFields();
        foreach ($selectFields as $key => $value) {
            unset($selectFields[$key]);
        }
        // Again.
        $tables = &$query->getTables();
        foreach ($tables as $key => $table) {
            unset($tables[$key]['all_fields']);
        }

        $query
            ->fields('q', array('id', 'msg_id', 'sub_id'))
            ->fields('m', array('chan_id'));

        // Create a temp table containing identifiers to update: this is
        // mandatory because you cannot use the apb_queue in the UPDATE
        // query subselect
        $tempTableName = $cx
            ->queryTemporary(
                (string)$query,
                $query->getArguments());

        return $tempTableName;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::update()
     *
     * // FIXME: Fix this (performance problem spotted)
     */
    public function update(array $values, array $conditions = null)
    {
        if (empty($values)) {
            return;
        }

        $cx     = $this->context->dbConnection;
        $tx     = null;
        $fields = array();

        foreach ($values as $field => $value) {
            switch ($field) {

                case CursorInterface::FIELD_MSG_UNREAD:
                    if (!$fields['unread'] = $value ? 1 : 0) {
                        // Also update the read timestamp if necessary
                        $fields['read_timestamp'] = time();
                    }
                    break;

                case CursorInterface::FIELD_MSG_READ_TS:
                    $fields['read_timestamp'] = (int)$value;
                    break;

                default:
                    trigger_error(sprintf("% does not support updating %d",
                        get_class($this), $field));
                    break;
            }
        }

        try {
            $tx = $cx->startTransaction();

            $tempTableName = $this->getTempTableNameFrom($conditions);

            $select = $cx
                ->select($tempTableName, 'tu')
                ->fields('tu', array('msg_id'));

            $update = $cx
                ->update('apb_queue');

            $update
                ->fields($fields)
                ->condition('msg_id', $select, 'IN')
                ->execute();

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::deleteMessages()
     */
    public function deleteMessages(array $conditions = null)
    {
        $cx = $this->context->dbConnection;
        $tx = null;

        try {
            $tx = $cx->startTransaction();

            $tempTableName = $this->getTempTableNameFrom($conditions);

            $select = $cx
                ->select($tempTableName, 'tu')
                ->fields('tu', array('id'));

            $cx
                ->delete('apb_queue')
                ->condition('id', $select, 'IN')
                ->execute();

            // This one can hurt very much, hope it wont in practical cases
            $select = $cx
                ->select('apb_queue', 'q')
                ->fields('q', array('msg_id'));
            $cx
                ->delete('apb_msg')
                ->condition('id', $select, 'NOT IN')
                ->execute();

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::flush()
     */
    public function flush()
    {
        $this->deleteAllMessages();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessage()
     */
    public function getMessage($id)
    {
        $cursor = $this->fetch(array(
            CursorInterface::FIELD_MSG_ID => $id,
        ));

        if (!count($cursor)) {
            throw new MessageDoesNotExistException();
        }

        foreach ($cursor as $message) {
            return $message;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::getMessages()
     */
    public function getMessages(array $idList)
    {
        $cursor = $this->fetch(array(
            CursorInterface::FIELD_MSG_ID => $idList,
        ));

        if (count($cursor) !== count($idList)) {
            throw new MessageDoesNotExistException();
        }

        return iterator_to_array($cursor);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getChannel()
     */
    public function getChannel($id)
    {
        if ($chan = $this->context->cache->getChannel($id)) {
            return $chan;
        }

        $record = $this
            ->context
            ->dbConnection
            ->query("SELECT * FROM {apb_chan} WHERE name = :name", array(':name' => $id))
            ->fetchObject();

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        $chan = new D7Channel(
            (int)$record->id,
            $record->name,
            $this->context,
            (int)$record->created);

        $this->context->cache->addChannel($chan);

        return $chan;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getChannels()
     */
    public function getChannels(array $idList)
    {
        if (empty($idList)) {
            return array();
        }

        $ret           = array();
        $missingIdList = array();

        // First populate cache from what we have
        foreach ($idList as $id) {
            if ($chan = $this->context->cache->getChannel($id)) {
                $ret[$id] = $chan;
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

            $chan = new D7Channel(
                (int)$record->id,
                $record->name,
                $this->context,
                (int)$record->created);

            $ret[$record->name] = $chan;

            $this->context->cache->addChannel($chan);
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
            if ($chan = $this->context->cache->getChannelByDatabaseId($id)) {
                $ret[$id] = $chan;
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

            $chan = new D7Channel(
                (int)$record->id,
                $record->name,
                $this->context,
                (int)$record->created);

            $ret[$record->name] = $chan;

            $this->context->cache->addChannel($chan);
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
        if ($chan = $this->context->cache->getChannel($id)) {
            return $chan;
        }

        $record = $this
            ->context
            ->dbConnection
            ->query("SELECT * FROM {apb_chan} WHERE id = :id", array(':id' => $id))
            ->fetchObject();

        if (!$record) {
            throw new ChannelDoesNotExistException();
        }

        $chan = new D7Channel(
            (int)$record->id,
            $record->name,
            $this->context,
            (int)$record->created);

        $this->context->cache->addChannel($chan);

        return $chan;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::createChannel()
     */
    public function createChannel($id, $ignoreErrors = false)
    {
        $chan    = null;
        $created = time();
        $dbId    = null;
        $cx      = $this->context->dbConnection;
        $tx      = null;

        try {
            $tx = $cx->startTransaction();

            // Do not ever use cache here, we cannot afford to try to create
            // a channel that have been deleted by another thread
            $exists = $cx
                ->query("SELECT 1 FROM {apb_chan} c WHERE c.name = :name", array(
                    ':name' => $id,
                ))
                ->fetchField();

            if ($exists) {
                if ($ignoreErrors) {
                    $chan = $this->getChannel($id);
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
                $chan = new D7Channel($dbId, $id, $this->context, $created);

                // In case of success, populate internal static cache
                $this->context->cache->addChannel($chan);
            }

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }

        return $chan;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::createChannels()
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
     * @see \APubSub\BackendInterface::deleteChannel()
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
     * @see \APubSub\BackendInterface::getSubscriptions()
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
                (int)$record->deactivated, (bool)$record->status,
                (array)unserialize($record->extra));

            $ret[$id] = $subscription;

            $this->context->cache->addSubscription($subscription);
        }

        array_multisort($idList, $ret);

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::deleteSubscription()
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
     * @see \APubSub\BackendInterface::getSubscriber()
     */
    public function getSubscriber($id)
    {
        // In this implementation all writes will be delayed on real operations
        return new D7Subscriber($this->context, $id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::subscribe()
     */
    public function subscribe($chanId, $subscriberId = null)
    {
        $deactivated = time();
        $created     = $deactivated;
        $cx          = $this->context->dbConnection;
        $tx          = null;
        $chan        = $this->getChannel($chanId);

        try {
            $tx = $cx->startTransaction();

            $cx
                ->insert('apb_sub')
                ->fields(array(
                    'chan_id'     => $chan->getDatabaseId(),
                    'status'      => 0,
                    'created'     => $created,
                    'deactivated' => $deactivated,
                ))
                ->execute();

            $id = (int)$cx->lastInsertId();

            $subscription = new D7Subscription(
                $chanId, $id, $created, 0,
                $deactivated, false, $this->context);

            $this->context->cache->addSubscription($subscription);

            return $subscription;

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::fetchSubscribers()
     */
    public function fetchSubscribers(array $conditions = null)
    {
        throw new \Exception("Not implemented yet");
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
        $cx     = $this->context->dbConnection;
        $tx     = null;
        $id     = null;
        $typeId = null;
        $chan   = $this->getChannel($chanId);
        $dbId   = $chan->getDatabaseId();

        if (null !== $type) {
            $typeId = $this
                ->context
                ->typeHelper
                ->getTypeId($type);
        }

        if (null === $sendTime) {
            $sendTime = time();
        }

        try {
            $tx = $cx->startTransaction();

            $cx
                ->insert('apb_msg')
                ->fields(array(
                    'chan_id'  => $dbId,
                    'created'  => $sendTime,
                    'contents' => serialize($contents),
                    'type_id'  => $typeId,
                ))
                ->execute();

            $id = (int)$cx->lastInsertId();

            // Send message to all subscribers
            $cx
                ->query("
                    INSERT INTO {apb_queue} (msg_id, sub_id, unread, created)
                        SELECT
                            :msgId   AS msg_id,
                            s.id     AS sub_id,
                            1        AS unread,
                            :created AS created
                        FROM {apb_sub} s
                        WHERE s.chan_id = :chanId
                        AND s.status = 1
                    ", array(
                        ':msgId'   => $id,
                        ':chanId'  => $dbId,
                        ':created' => $sendTime,
                    ));

            unset($tx); // Excplicit commit

            if (!$this->context->delayChecks) {
                $this->context->getBackend()->cleanUpMessageQueue();
                $this->context->getBackend()->cleanUpMessageLifeTime();
            }
        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }

        return new DefaultMessage(
            $this->context, $chanId, null, $contents, $id,
            $sendTime, null, true, null, $level);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::flushCaches()
     */
    public function flushCaches()
    {
        $this->context->flushCaches();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::garbageCollection()
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
