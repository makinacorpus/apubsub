<?php

namespace APubSub\Backend\Drupal7;

use APubSub\CursorInterface;
use APubSub\Field;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
class D7ChannelCursor extends AbstractD7Cursor
{
    public function getAvailableSorts()
    {
        return array(
            Field::CHAN_ID,
            Field::CHAN_CREATED_TS
        );
    }

    protected function applyConditions(array $conditions)
    {
        $ret = array();

        foreach ($conditions as $field => $value) {
            switch ($field) {

                case Field::CHAN_ID:
                    $ret['c.name'] = $value;
                    break;

                case Field::CHAN_CREATED_TS:
                    $ret['c.created'] = $value;
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
            $query->orderBy('c.id', 'ASC');
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
                        $query->orderBy('c.name', $direction);
                        break;

                    case Field::CHAN_CREATED_TS:
                        $query->orderBy('c.created', $direction);
                        break;

                    default:
                        throw new \InvalidArgumentException("Unsupported sort field");
                }
            }
        }
    }

    protected function createObjectInstance(\stdClass $record)
    {
        return new D7Channel(
            (int)$record->id,
            $record->name,
            $this->context,
            (int)$record->created,
            empty($record->title) ? null : $record->title);
    }

    protected function buildQuery()
    {
        return $this
            ->context
            ->dbConnection
            ->select('apb_chan', 'c')
            ->fields('c');
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

        $query->fields('c', array('id'));

        // Create a temp table containing identifiers to update: this is
        // mandatory because you cannot use the apb_queue in the UPDATE
        // query subselect
        $cx = $this->context->dbConnection;
        $tempTableName = $cx->queryTemporary((string)$query, $query->getArguments());
        $cx->schema()->addIndex($tempTableName, $tempTableName . '_idx', array('id'));

        return $tempTableName;
    }

    public function delete()
    {
        $cx = $this->context->dbConnection;
        $tx = null;

        try {
            $tx = $cx->startTransaction();

            // Deleting messages in queue implicates doing it using the queue id:
            // because the 'apb_queue' table is our primary FROM table (in most
            // cases) we need to proceed using a temporary table
            $tempTableName = $this->createTempTable();

            switch ($cx->driver()) {

                case 'mysql':
                    $cx->query("
                        DELETE m.* FROM {apb_msg} m
                        JOIN {apb_msg_chan} c ON c.msg_id = m.id
                        JOIN {" . $tempTableName . "} t ON t.id = c.chan_id
                    ");

                    $cx->query("
                        DELETE c.* FROM {apb_msg_chan} c
                        JOIN {" . $tempTableName . "} t ON t.id = c.chan_id
                    ");

                    // FIXME: Performance problem right here
                    // Explore ON DELETE CASCADE (problem with Drupal here)
                    $cx->query("
                        DELETE
                        FROM {apb_queue}
                        WHERE
                            msg_id NOT IN (
                                SELECT id
                                FROM {apb_msg}
                            )
                    ");

                    $cx->query("
                        DELETE s.*
                        FROM {apb_sub} s
                        JOIN {" . $tempTableName . "} t ON t.id = s.chan_id
                    ");

                    $cx->query("
                        DELETE c.*
                        FROM {apb_chan} c
                        JOIN {" . $tempTableName . "} t ON t.id = c.id
                    ");
                    break;

                default:
                    $cx->query("
                        DELETE FROM {apb_msg}
                        WHERE
                            id IN (
                                SELECT msg_id
                                FROM {apb_msg_chan}
                                WHERE
                                    chan_id IN (
                                        SELECT id
                                        FROM {" . $tempTableName ."}
                                    )
                            )
                    ");

                    $cx->query("
                        DELETE
                        FROM {apb_msg_chan}
                        WHERE
                            chan_id IN (
                                SELECT id
                                FROM {" . $tempTableName ."}
                            )
                    ");

                    // FIXME: Performance problem right here
                    // Explore ON DELETE CASCADE (problem with Drupal here)
                    $cx->query("
                        DELETE
                        FROM {apb_queue}
                        WHERE
                            msg_id NOT IN (
                                SELECT id
                                FROM {apb_msg}
                            )
                    ");

                    $cx->query("
                        DELETE
                        FROM {apb_sub}
                        WHERE
                            chan_id IN (
                                SELECT id
                                FROM {" . $tempTableName . "}
                            )
                    ");
                    $cx->query("
                        DELETE
                        FROM {apb_chan}
                        WHERE
                            id IN (
                                SELECT id
                                FROM {" . $tempTableName ."}
                            )
                    ");
                    break;
            }

            $cx->query("DROP TABLE {" . $tempTableName . "}");

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

    public function update(array $values)
    {
        if (empty($values)) {
            return;
        }

        $queryValues = array();

        // First build values and ensure the users don't do anything stupid
        foreach ($values as $key => $value) {
            switch ($key) {

                case Field::CHAN_TITLE:
                    $queryValues['title'] = $value;
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
            ->update('apb_chan')
            ->fields($queryValues)
            ->condition('id', $select, 'IN')
            ->execute();

        $cx->query("DROP TABLE {" . $tempTableName . "}");
    }
}
