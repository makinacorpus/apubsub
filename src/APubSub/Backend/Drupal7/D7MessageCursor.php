<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultMessageInstance;
use APubSub\ContextInterface;
use APubSub\CursorInterface;
use APubSub\Field;

class D7MessageCursor extends AbstractD7Cursor
{
    /**
     * @var boolean
     */
    private $queryOnSuber = false;

    /**
     * @var boolean
     */
    private $distinct = true;

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            Field::CHAN_ID,
            Field::MSG_ID,
            Field::MSG_SENT,
            Field::MSG_TYPE,
            Field::MSG_LEVEL,
            Field::MSG_READ_TS,
            Field::MSG_UNREAD,
            Field::SUB_ID,
        );
    }

    protected function applyConditions(array $conditions)
    {
        $ret = array();

        foreach ($conditions as $field => $value) {
            switch ($field) {

                case Field::MSG_ID:
                    $ret['q.msg_id'] = $value;
                    break;

                case Field::MSG_UNREAD:
                    $ret['q.unread'] = $value;
                    break;

                case Field::MSG_QUEUE_ID:
                    $ret['q.id'] = $value;
                    break;

                case Field::MSG_TYPE:

                    $typeRegistry = $this->getContext()->typeRegistry;

                    if (is_array($value)) {
                        array_walk($value, function (&$value) use ($typeRegistry) {
                            $value = $typeRegistry->getTypeId($value);
                        });
                    } else {
                        $value = $typeRegistry->getTypeId($value);
                    }

                    $ret['m.type_id'] = $value;
                    break;

                case Field::SUB_ID:
                    $ret['q.sub_id'] = $value;
                    break;

                case Field::SUBER_NAME:
                    $ret['mp.name'] = $value;
                    $this->queryOnSuber = true;
                    break;


                case Field::CHAN_ID:
                    // FIXME: Find a better way
                    $ret['m.chan_id'] = $this
                        ->context
                        ->dbConnection
                        ->query("
                                SELECT id
                                FROM {apb_chan}
                                WHERE name IN (:name)",
                        array(
                            ':name' => $value,
                        ))->fetchCol();
                    break;

                default:
                    trigger_error(sprintf("% does not support filter %d yet",
                        get_class($this), $field));
                    break;
            }
        }

        return $ret;
    }

    protected function applySorts(\SelectQueryInterface $query, array $sorts)
    {
        if (empty($sorts)) {
            // Messages need a default ordering for fetching. If time for
            // more than one message is the same, ordering by message
            // identifier as second choice will lower unpredictable
            // behavior chances to happen (still possible thought since
            // serial fields don't guarantee order, even thought in real
            // life they do until very high values)
            $query
                ->orderBy('q.created', 'ASC')
                ->orderBy('q.msg_id', 'ASC');
        } else {
            foreach ($sorts as $sort => $order) {

                if ($order === CursorInterface::SORT_DESC) {
                    $direction = 'DESC';
                } else {
                    $direction = 'ASC';
                }

                switch ($sort)
                {
                    case Field::CHAN_ID:
                        $this->query->orderBy('m.chan_id', $direction);
                        break;

                    case Field::MSG_ID:
                    case Field::MSG_SENT:
                        $query
                            ->orderBy('q.created', $direction)
                            ->orderBy('q.msg_id', $direction);
                        break;

                    case Field::MSG_TYPE:
                        $query->orderBy('m.type', $direction);
                        break;

                    case Field::MSG_READ_TS:
                        $query->orderBy('m.read_timestamp', $direction);
                        break;

                    case Field::MSG_UNREAD:
                        $query->orderBy('q.msg_id', $direction);
                        break;

                    case Field::MSG_LEVEL:
                        $query->orderBy('m.level', $direction);
                        break;

                    case Field::SUB_ID:
                        $query->orderBy('q.sub_id', $direction);
                        break;

                    default:
                        throw new \InvalidArgumentException("Unsupported sort field");
                }
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Cursor\AbstractD7Cursor::createObjectInstance()
     */
    protected function createObjectInstance(\stdClass $record)
    {
        if ($record->read_timestamp) {
            $readTime = (int)$record->read_timestamp;
        } else {
            $readTime = null;
        }

        return new DefaultMessageInstance(
            $this->context,
            (string)$record->chan_id,
            (int)$record->sub_id,
            unserialize($record->contents),
            (int)$record->id,
            (int)$record->created,
            $this->context->typeRegistry->getType($record->type_id),
            (bool)$record->unread,
            $readTime,
            (int)$record->level);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Cursor\AbstractD7Cursor::buildQuery()
     */
    protected function buildQuery()
    {
        /*
         * Targeted query: benchmarked along 4 different variants, including
         * subqueries, different JOIN order, different indexes: this one
         * is the one that will give you the best performances with MySQL.
         *
         * SELECT q.*, m.* FROM apb_sub_map mp
         *     JOIN apb_queue q ON q.sub_id = mp.sub_id
         *     JOIN apb_msg m ON m.id = q.msg_id
         *     WHERE mp.name = 'user:9991'
         *     ORDER BY m.id ASC;
         *
         * MySQL EXPLAIN was specific enough in all variants to say without
         * any doubt this is the best one, fully using indexes, starting with
         * a CONST index, and using only ref and eq_ref JOIN types on known
         * INT32 indexes.
         *
         * On a poor box, with few CPU and few RAM this query runs in 0.01s
         * (MySQL result) with no query cache and 5 millions of records in
         * the apb_queue table and 300,000 in the apb_sub_map table.
         *
         * Note that for other DBMS' this will need to be tested, and a
         * switch/case on the dbConnection class may proove itself to be very
         * efficient if needed.
         *
         * Additionally, we need to apply some conditions over this query:
         *
         *     WHERE
         *       [CONDITIONS]
         *     ORDER BY [FIELD] [DIRECTION];
         *
         * Hopping those won't kill our queries.
         *
         * Note that if no conditions are set on the subscriber table the
         * FROM table will be different.
         */

        if ($this->queryOnSuber) {

            $query = $this
                ->context
                ->dbConnection
                ->select('apb_sub_map', 'mp');

            // @todo Smart conditions for subscriber and subscription
            $query
                ->join('apb_queue', 'q', 'q.sub_id = mp.sub_id');
            $query
                ->join('apb_msg', 'm', 'm.id = q.msg_id');
            $query
                ->fields('m')
                ->fields('q');
        } else {

            $query = $this
                ->context
                ->dbConnection
                ->select('apb_queue', 'q');
            $query
                ->join('apb_msg', 'm', 'm.id = q.msg_id');
            $query
                ->fields('m')
                ->fields('q');
        }

        // Disallow message duplicates, remember that trying to read the
        // unread or read timestamp status when requesting from a channel
        // makes no sense
        // You'd also have to consider that when we're dealing with UPDATE
        // or DELETE operations we want the full result list in order to
        // correctly wipe out the queue
        if ($this->distinct) {
            $query->groupBy('q.msg_id');
        }

        return $query;
    }

    /**
     * Create temporary table from current query
     *
     * @return string New temporary table name, filled in with query primary
     *                identifiers only
     */
    private function createTempTable()
    {
        $query = clone $this->getQuery();
        $query->distinct(false);

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
        // And again.
        $groupBy = &$query->getGroupBy();
        foreach ($groupBy as $key => $value) {
            unset($groupBy[$key]);
        }

        $query->fields('q', array('id'));

        // Create a temp table containing identifiers to update: this is
        // mandatory because you cannot use the apb_queue in the UPDATE
        // query subselect
        $tempTableName = $this
            ->context
            ->dbConnection
            ->queryTemporary(
                (string)$query,
                $query->getArguments());

        return $tempTableName;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::update()
     */
    public function delete()
    {
        // Deleting messages in queue implicates doing it using the queue id:
        // because the 'apb_queue' table is our primary FROM table (in most
        // cases) we need to proceed using a temporary table
        $tempTableName = $this->createTempTable();

        $cx = $this->context->dbConnection;

        $cx->query("
            DELETE FROM {apb_queue}
            WHERE
                id IN (
                    SELECT id
                    FROM " . $tempTableName ."
                )
        ");

        $cx->query("DROP TABLE {" . $tempTableName . "}");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::update()
     */
    public function update(array $values)
    {
        if (empty($values)) {
            return;
        }

        $queryValues = array();

        // First build values and ensure the users don't do anything stupid
        foreach ($values as $key => $value) {
            switch ($key) {

                case Field::MSG_UNREAD:
                    $queryValues['unread'] = (int)$value;
                    break;

                case Field::MSG_READ_TS:
                    $queryValues['read_timestamp'] = (int)$value;
                    break;

                default:
                    throw new \RuntimeException(sprintf(
                        "%s field is unsupported for update",
                        $key));
            }
        }

        // Updating messages in queue implicates doing it using the queue id:
        // because the 'apb_queue' table is our primary FROM table (in most
        // cases) we need to proceed using a temporary table
        $tempTableName = $this->createTempTable();

        $cx = $this->context->dbConnection;

        $select = $cx
            ->select($tempTableName, 't')
            ->fields('t', array('id'));

        $cx
            ->update('apb_queue')
            ->fields($queryValues)
            ->condition('id', $select, 'IN')
            ->execute();

        $cx->query("DROP TABLE {" . $tempTableName . "}");
    }
}