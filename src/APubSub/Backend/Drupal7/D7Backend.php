<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractBackend;
use APubSub\Backend\DefaultMessageInstance;
use APubSub\Backend\DefaultSubscriber;
use APubSub\Backend\DefaultSubscription;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\Field;

/**
 * Drupal 7 backend implementation
 */
class D7Backend extends AbstractBackend
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
            $cursor->setConditions($conditions);
        }

        return $cursor;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::fetchChannels()
     */
    public function fetchChannels(array $conditions = null)
    {
        $cursor = new D7ChannelCursor($this->context);

        if (!empty($conditions)) {
            $cursor->setConditions($conditions);
        }

        return $cursor;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::createChannel()
     */
    public function createChannel($id, $title = null, $ignoreErrors = false)
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
                        'title' => $title,
                        'created' => $created,
                    ))
                    ->execute();

                // Specify the name of the sequence object for PDO_PGSQL.
                // @see http://php.net/manual/en/pdo.lastinsertid.php.
                $seq = ($cx->driver() === 'pgsql') ? 'apb_chan_id_seq' : null;
                $dbId = (int)$cx->lastInsertId($seq);

                $chan = new D7Channel($dbId, $id, $this->context, $created, $title);
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

            if (count($existingList) !== count($idList)) {

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
            }

            // We can get back an id with the last insert id, but if the
            // user created 10 chans, better do 2 SQL queries than 10!
            // This is also the smart part of this function: we will load
            // all channels at once instead of doing a first query for
            // existing and a second for newly created ones, thus allowing
            // us to keep the list ordered implicitely as long as the get
            // method keeps it ordered too
            $ret = $this->fetchChannels(array(
                Field::CHAN_ID => $idList,
            ));

            unset($tx); // Explicit commit
        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return iterator_to_array($ret);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::fetchSubscriptions()
     */
    public function fetchSubscriptions(array $conditions = null)
    {
        $cursor = new D7SubscriptionCursor($this->context);

        if (!empty($conditions)) {
            $cursor->setConditions($conditions);
        }

        return $cursor;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getSubscriber()
     */
    public function getSubscriber($id)
    {
        $idList = $this
            ->context
            ->dbConnection
            // This query will also remove non existing stalling subscriptions
            // from the subscriber map thanks to the JOIN statements, thus
            // avoiding potential exceptions being thrown at single subscription
            // get time
            ->query("
                SELECT c.name, mp.sub_id
                    FROM {apb_sub_map} mp
                    JOIN {apb_sub} s ON s.id = mp.sub_id
                    JOIN {apb_chan} c ON c.id = s.chan_id
                    WHERE mp.name = :name", array(
                ':name' => $id,
            ))
            ->fetchAllKeyed();

        return new DefaultSubscriber($id, $this->context, $idList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::deleteSubscriber()
     */
    public function deleteSubscriber($id)
    {
        $cx         = $this->context->dbConnection;
        $tx         = null;
        $subscriber = $this->getSubscriber($id);

        try {
            $tx   = $cx->startTransaction();

            $args = array(':id' => $id);

            // FIXME: SELECT FOR UPDATE here in all tables

            // Start by deleting all subscriptions.
            $subIdList = array();
            foreach ($subscriber->getSubscriptions() as $subscription) {
                $subIdList[] = $subscription->getId();
            }

            if (!empty($subIdList)) {
                $cursor = $this
                    ->fetchSubscriptions(array(
                        Field::SUB_ID => $subIdList,
                    ))
                    ->delete();
            }

            $cx->query("DELETE FROM {apb_sub_map} WHERE name = :id", $args);

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
     * @see \APubSub\BackendInterface::deleteSubscribers()
     */
    public function deleteSubscribers($idList)
    {
        // FIXME: Find a more elegant way of doing this
        foreach ($idList as $id) {
            $this->deleteSubscriber($id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::subscribe()
     */
    public function subscribe($chanId, $subscriberId = null)
    {
        $deactivated  = time();
        $created      = $deactivated;
        $cx           = $this->context->dbConnection;
        $tx           = null;
        $chan         = $this->getChannel($chanId);
        $subscriber   = null;
        $subscription = null;

        if ($subscriberId) {
            $subscriber = $this->getSubscriber($subscriberId);

            if ($subscriber->hasSubscriptionFor($chanId)) {
                return $subscriber->getSubscriptionFor($chanId);
            }
        }

        try {
            $tx = $cx->startTransaction();

            $cx
                ->insert('apb_sub')
                ->fields(array(
                    'chan_id'     => $chan->getDatabaseId(),
                    'status'      => (int)(bool)$subscriberId,
                    'created'     => $created,
                    'deactivated' => $deactivated,
                ))
                ->execute();

            // Specify the name of the sequence object for PDO_PGSQL.
            // @see http://php.net/manual/en/pdo.lastinsertid.php.
            $seq = ($cx->driver() === 'pgsql') ? 'apb_sub_id_seq' : null;
            $id = (int)$cx->lastInsertId($seq);

            // Implicitely create the new subscriber/subscription association
            // if a subscriber identifier was given
            if ($subscriber) {

                $exists = $cx
                    ->select('apb_sub_map', 'mp')
                    ->fields('mp', array('name'))
                    ->condition('mp.name', $subscriberId)
                    ->condition('mp.sub_id', $id)
                    ->execute()
                    ->fetchField();

                if (!$exists) {
                    $cx
                        ->insert('apb_sub_map')
                        ->fields(array(
                            'name'   => $subscriberId,
                            'sub_id' => $id,
                        ))
                        ->execute();
                }
            }

            $subscription = new DefaultSubscription(
                $chanId, $id, $created, 0,
                $deactivated, false, $this->context);

            unset($tx); // Explicit commit

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
                ->typeRegistry
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
                    'level'    => $level,
                ))
                ->execute();

            $seq = ($cx->driver() === 'pgsql') ? 'apb_msg_id_seq' : null;
            $id = (int)$cx->lastInsertId($seq);

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

        return new DefaultMessageInstance(
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
        // Drop all messages for inactive subscriptions
        $this
            ->context
            ->dbConnection
            ->query("
                    DELETE
                    FROM {apb_queue}
                    WHERE sub_id IN (
                        SELECT id
                        FROM {apb_sub}
                        WHERE status = 0
                    )
                ");

        // Clean up expired messages
        if ($this->context->messageMaxLifetime) {
            $this
                ->context
                ->dbConnection
                ->query("DELETE FROM {apb_msg} WHERE created < :time", array(
                    ':time' => time() - $this->context->messageMaxLifetime,
                ));
        }

        // Limit queue size if configured for
        if ($this->context->queueGlobalLimit) {

            $min = $this
                ->context
                ->dbConnection
                ->query("
                        SELECT msg_id
                        FROM {apb_queue}
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

        // Clean up orphaned messages
        $this
            ->context
            ->dbConnection
            ->query("
                    DELETE
                    FROM {apb_msg}
                    WHERE id NOT IN (
                        SELECT msg_id
                        FROM {apb_queue}
                    )
                ");

        // Clean up orphaned subsribers
        $this
            ->context
            ->dbConnection
            ->query("
                    DELETE
                    FROM {apb_sub_map}
                    WHERE sub_id NOT IN (
                        SELECT id
                        FROM {apb_sub}
                    )
                ");
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
