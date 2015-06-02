<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractBackend;
use APubSub\Backend\DefaultMessage;
use APubSub\Backend\DefaultSubscriber;
use APubSub\Backend\DefaultSubscription;
use APubSub\Error\ChannelAlreadyExistsException;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\Field;
use APubSub\Misc;

/**
 * Drupal 7 backend implementation
 */
class D7Backend extends AbstractBackend
{
    /**
     * @var D7Backend
     */
    protected $backend;

    /**
     * @var \DatabaseConnection
     */
    protected $db;

    /**
     * @var TypeRegistry
     */
    protected $typeRegistry;

    /**
     * The most efficient caching point (and probably the only one that
     * really is useful) is the subscriber cache
     *
     * @param DefaultSubscriber[]
     *   List of cache subscribers keyed by name
     */
    protected $subscribersCache = array();

    /**
     * Default constructor
     *
     * @param DatabaseConnection $dbConnection
     *   Drupal database connexion
     * @param array|Traversable $options
     *   Options
     */
    public function __construct(\DatabaseConnection $dbConnection, $options = null)
    {
        $this->db = $dbConnection;
        $this->typeRegistry = new TypeRegistry($this);
    }

    /**
     * Get database connection
     *
     * @return \DatabaseConnection
     */
    public function getConnection()
    {
        return $this->db;
    }

    /**
     * Get type registry
     *
     * @return TypeRegistry
     */
    public function getTypeRegistry()
    {
        return $this->typeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(array $conditions = null)
    {
        $cursor = new D7MessageCursor($this);

        if (!empty($conditions)) {
            $cursor->setConditions($conditions);
        }

        return $cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchChannels(array $conditions = null)
    {
        $cursor = new D7ChannelCursor($this);

        if (!empty($conditions)) {
            $cursor->setConditions($conditions);
        }

        return $cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function createChannel($id, $title = null, $ignoreErrors = false)
    {
        $chan    = null;
        $created = new \DateTime();
        $dbId    = null;
        $cx      = $this->db;
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
                $dateStr = $created->format(Misc::SQL_DATETIME);
                $cx
                    ->insert('apb_chan')
                    ->fields([
                        'name'      => $id,
                        'title'     => $title,
                        'created'   => $dateStr,
                        'updated'   => $dateStr,
                    ])
                    ->execute()
                ;

                // Specify the name of the sequence object for PDO_PGSQL.
                // @see http://php.net/manual/en/pdo.lastinsertid.php.
                $seq = ($cx->driver() === 'pgsql') ? 'apb_chan_id_seq' : null;
                $dbId = (int)$cx->lastInsertId($seq);

                $chan = new D7Channel($dbId, $id, $this, $created, null, $title);
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
     * {@inheritdoc}
     */
    public function createChannels($idList, $ignoreErrors = false)
    {
        if (empty($idList)) {
            return [];
        }

        $ret            = [];
        $created        = new \DateTime();
        $createdString  = $created->format(Misc::SQL_DATETIME);
        $cx             = $this->db;
        $tx             = $cx->startTransaction();

        try {
            // Do not ever use cache here, we cannot afford to try to create
            // a channel that have been deleted by another thread
            $existingList = $cx
                ->select('apb_chan', 'c')
                ->fields('c', ['name'])
                ->condition('c.name', $idList, 'IN')
                ->execute()
                ->fetchCol()
            ;

            if ($existingList && !$ignoreErrors) {
                throw new ChannelAlreadyExistsException();
            }

            if (count($existingList) !== count($idList)) {

                $query = $cx
                    ->insert('apb_chan')
                    ->fields(['name', 'created', 'updated'])
                ;

                // Create only the non existing one, in one query
                foreach (array_diff($idList, $existingList) as $id) {
                    $query->values([$id, $createdString, $createdString]);
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
            $ret = $this->fetchChannels([Field::CHAN_ID => $idList]);

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            $tx->rollback();

            throw $e;
        }

        return iterator_to_array($ret);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchSubscriptions(array $conditions = null)
    {
        $cursor = new D7SubscriptionCursor($this);

        if (!empty($conditions)) {
            $cursor->setConditions($conditions);
        }

        return $cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriber($id)
    {
        if (isset($this->subscribersCache[$id])) {
            return $this->subscribersCache[$id];
        }

        $idList = $this
            ->db
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

        return $this->subscribersCache[$id] = new DefaultSubscriber($id, $this, $idList);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSubscriber($id)
    {
        $cx         = $this->db;
        $tx         = null;
        $subscriber = $this->getSubscriber($id);

        try {
            $tx   = $cx->startTransaction();

            $args = [':id' => $id];

            // FIXME: SELECT FOR UPDATE here in all tables

            // Start by deleting all subscriptions.
            $subIdList = [];
            foreach ($subscriber->getSubscriptions() as $subscription) {
                $subIdList[] = $subscription->getId();
            }

            if (!empty($subIdList)) {
                $this
                    ->fetchSubscriptions([Field::SUB_ID => $subIdList])
                    ->delete()
                ;
            }

            $cx->query("DELETE FROM {apb_sub_map} WHERE name = :id", $args);

            unset($tx); // Explicit commit

            if (isset($this->subscribersCache[$id])) {
                unset($this->subscribersCache[$id]);
            }

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
     * {@inheritdoc}
     */
    public function deleteSubscribers($idList)
    {
        // FIXME: Find a more elegant way of doing this
        foreach ($idList as $id) {
            $this->deleteSubscriber($id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($chanId, $subscriberId = null)
    {
        $deactivated  = new \DateTime();
        $created      = $deactivated;
        $cx           = $this->db;
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
                ->fields([
                    'chan_id'     => $chan->getDatabaseId(),
                    'status'      => (int)(bool)$subscriberId,
                    'created'     => $created->format(Misc::SQL_DATETIME),
                    'activated'   => $deactivated->format(Misc::SQL_DATETIME),
                    'deactivated' => $deactivated->format(Misc::SQL_DATETIME),
                ])
                ->execute()
            ;

            // Specify the name of the sequence object for PDO_PGSQL.
            // @see http://php.net/manual/en/pdo.lastinsertid.php.
            $seq = ($cx->driver() === 'pgsql') ? 'apb_sub_id_seq' : null;
            $id = (int)$cx->lastInsertId($seq);

            // Implicitely create the new subscriber/subscription association
            // if a subscriber identifier was given
            if ($subscriber) {

                $exists = $cx
                    ->select('apb_sub_map', 'mp')
                    ->fields('mp', ['name'])
                    ->condition('mp.name', $subscriberId)
                    ->condition('mp.sub_id', $id)
                    ->execute()
                    ->fetchField()
                ;

                if (!$exists) {
                    $cx
                        ->insert('apb_sub_map')
                        ->fields([
                            'name'   => $subscriberId,
                            'sub_id' => $id,
                        ])
                        ->execute()
                    ;
                }
            }

            $subscription = new DefaultSubscription(
                $chanId, $id, $created, $deactivated,
                $deactivated, false, $this
            );

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
     * {@inheritdoc}
     */
    public function fetchSubscribers(array $conditions = null)
    {
        $cursor = new D7SubscriberCursor($this);

        if (!empty($conditions)) {
            $cursor->setConditions($conditions);
        }

        return $cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function send(
        $chanId,
        $contents,
        $type               = null,
        $origin             = null,
        $level              = 0,
        array $excluded     = null,
        \DateTime $sentAt   = null)
    {
        if (!is_array($chanId)) { // Ensure this is a list
            $chanId = array($chanId);
        }
        if (empty($chanId)) { // Short-circuit empty chan list
            return;
        }

        $cx       = $this->db;
        $tx       = null;
        $id       = null;
        $typeId   = 0;
        $chanList = $this->getChannels($chanId);
        $dbIdList = array();

        foreach ($chanList as $channel) {
            $dbIdList[] = $channel->getDatabaseId();
        }

        if (null !== $type) {
            $typeId = $this->typeRegistry->getTypeId($type);
        }

        if (null === $sentAt) {
            $sentAt = new \DateTime();
        }

        try {
            $tx = $cx->startTransaction();

            $cx
                ->insert('apb_msg')
                ->fields([
                    'created'  => $sentAt->format(Misc::SQL_DATETIME),
                    'contents' => serialize($contents),
                    'type_id'  => $typeId,
                    'level'    => $level,
                    'origin'   => $origin,
                ])
                ->execute()
            ;

            $seq = ($cx->driver() === 'pgsql') ? 'apb_msg_id_seq' : null;
            $id = (int)$cx->lastInsertId($seq);

            // Insert channel references
            $q = $cx->insert('apb_msg_chan')->fields(['msg_id', 'chan_id']);
            foreach ($dbIdList as $dbId) {
                $q->values([$id, $dbId]);
            }
            $q->execute();

            // Send message to all subscribers
            if (empty($excluded)) {
                $cx
                    ->query("
                        INSERT INTO {apb_queue} (msg_id, sub_id, type_id, unread, created)
                            SELECT
                                :msgId   AS msg_id,
                                s.id     AS sub_id,
                                :typeId  AS type_id,
                                1        AS unread,
                                :created AS created
                            FROM {apb_sub} s
                            WHERE s.chan_id IN (:chanId)
                            AND s.status = 1
                        ", [
                            ':msgId'   => $id,
                            ':typeId'  => $typeId,
                            ':chanId'  => $dbIdList,
                            ':created' => $sentAt->format(Misc::SQL_DATETIME),
                        ])
                ;
            } else {
                $cx
                    ->query("
                        INSERT INTO {apb_queue} (msg_id, sub_id, type_id, unread, created)
                            SELECT
                                :msgId   AS msg_id,
                                s.id     AS sub_id,
                                :typeId  AS type_id,
                                1        AS unread,
                                :created AS created
                            FROM {apb_sub} s
                            WHERE
                                s.chan_id IN (:chanId)
                                AND s.status = 1
                                AND s.id NOT IN (:excluded)
                        ", [
                            ':msgId'    => $id,
                            ':typeId'  => $typeId,
                            ':chanId'   => $dbIdList,
                            ':created'  => $sentAt->format(Misc::SQL_DATETIME),
                            ':excluded' => $excluded,
                        ])
                ;
            }

            $cx
                ->update('apb_chan')
                ->fields([
                    'updated' => (new \DateTime())->format(Misc::SQL_DATETIME),
                ])
                ->condition('id', $dbIdList)
                ->execute()
            ;

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }

        return new DefaultMessage($this, $contents, $id, $type, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function copyQueue($chanId, $subIdList, $isUnread = true)
    {
        if (!is_array($subIdList)) {
            $subIdList = [$subIdList];
        }

        if (!$channel = $this->getChannel($chanId)) {
            throw new ChannelDoesNotExistException();
        }

        // FIXME: Could do better than foreach.
        foreach ($subIdList as $subId) {
            $this
                ->getConnection()
                ->query("
                    INSERT INTO {apb_queue} (msg_id, sub_id, type_id, created, unread)
                    SELECT m.id, :subId, m.type_id, m.created, :isUnread
                    FROM apb_msg m
                    JOIN apb_msg_chan mc ON m.id = mc.msg_id
                    WHERE
                        mc.chan_id = :chanId
                        AND NOT EXISTS (
                            SELECT 1 FROM apb_queue q
                            WHERE q.msg_id = m.id AND q.sub_id = :subId2
                        )
                ", [
                    ':subId'    => $subId,
                    ':isUnread' => (int)$isUnread,
                    ':chanId'   => $channel->getDatabaseId(),
                    ':subId2'   => $subId,
                ])
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flushCaches()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function garbageCollection()
    {
        // Drop all messages for inactive subscriptions
        $this
            ->db
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
        if (false /* Max message lifetime */) {
            $this
                ->db
                ->query("DELETE FROM {apb_msg} WHERE created < :time", array(
                    ':time' => time() - $this->context->messageMaxLifetime,
                ));
        }

        // Limit queue size if configured for
        if (false /* Global queue limit */) {

            $min = $this
                ->db
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
                    ->db
                    ->query("DELETE FROM {apb_queue} WHERE msg_id < :min", array(
                        ':min' => $min,
                    ));
            }
        }

        // Clean up orphaned messages
        $this
            ->db
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
            ->db
            ->query("
                    DELETE
                    FROM {apb_sub_map}
                    WHERE sub_id NOT IN (
                        SELECT id
                        FROM {apb_sub}
                    )
                ");
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalysis()
    {
        $cx = $this->db;

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
