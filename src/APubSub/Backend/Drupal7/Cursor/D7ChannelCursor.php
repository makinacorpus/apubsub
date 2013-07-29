<?php

namespace APubSub\Backend\Drupal7\Cursor;

use APubSub\Backend\Drupal7\D7Channel;
use APubSub\CursorInterface;
use APubSub\Field;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
class D7ChannelCursor extends AbstractD7Cursor
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            Field::CHAN_ID,
            Field::CHAN_CREATED_TS
        );
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Cursor\AbstractD7Cursor::applyConditions()
     */
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

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Cursor\AbstractD7Cursor::applySorts()
     */
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

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Cursor\AbstractD7Cursor::createObjectInstance()
     */
    protected function createObjectInstance(\stdClass $record)
    {
        return new D7Channel(
            (int)$record->id,
            $record->name,
            $this->context,
            (int)$record->created);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Cursor\AbstractD7Cursor::buildQuery()
     */
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
        $cx = $this->context->dbConnection;
        $tx = null;

        try {
            $tx = $cx->startTransaction();

            // Deleting messages in queue implicates doing it using the queue id:
            // because the 'apb_queue' table is our primary FROM table (in most
            // cases) we need to proceed using a temporary table
            $tempTableName = $this->createTempTable();

            $cx->query("
                DELETE
                FROM {apb_msg}
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

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::update()
     */
    public function update(array $values)
    {
        if (empty($values)) {
            return;
        } else {
            throw new \RuntimeException("Cannot update a channel");
        }
    }
}
