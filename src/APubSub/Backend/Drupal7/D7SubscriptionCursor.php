<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultSubscription;
use APubSub\CursorInterface;
use APubSub\Field;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
class D7SubscriptionCursor extends AbstractD7Cursor
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            Field::SUB_ID,
            Field::SUB_STATUS,
            Field::SUB_CREATED_TS,
            Field::CHAN_ID,
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

                case Field::SUB_ID:
                    $ret['s.id'] = $value;
                    break;

                case Field::SUB_STATUS:
                    $ret['s.status'] = $value;
                    break;

                case Field::SUB_CREATED_TS:
                    $ret['s.created'] = $value;
                    break;

                case Field::CHAN_ID:
                    $ret['c.name'] = $value;
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
            $query->orderBy('s.id', 'ASC');
        } else {
            foreach ($sorts as $sort => $order) {

                if ($order === CursorInterface::SORT_DESC) {
                    $direction = 'DESC';
                } else {
                    $direction = 'ASC';
                }

                switch ($sort)
                {
                    case Field::SUB_ID:
                        $query->orderBy('s.id', $direction);
                        break;

                    case Field::SUB_STATUS:
                        $query->orderBy('s.status', $direction);
                        break;

                    case Field::SUB_CREATED_TS:
                        $query->orderBy('s.created', $direction);
                        break;

                    case Field::CHAN_ID:
                        $query->orderBy('c.name', $direction);
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
        return new DefaultSubscription(
            $record->name,
            (int)$record->id,
            (int)$record->created,
            (int)$record->activated,
            (int)$record->deactivated,
            (bool)$record->status,
            $this->context);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Cursor\AbstractD7Cursor::buildQuery()
     */
    protected function buildQuery()
    {
        $query = $this
            ->context
            ->dbConnection
            ->select('apb_sub', 's')
            ->fields('s');

        // FIXME: Get rid of JOIN
        $query
            ->join('apb_chan', 'c', 's.chan_id = c.id');

        $query
            ->fields('c', array('name'));

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

        $query->fields('s', array('id'));

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

            // FIXME: Performance problem right here
            // Explore ON DELETE CASCADE (problem with Drupal here)
            $cx->query("
                DELETE
                FROM {apb_queue}
                WHERE
                    sub_id IN (
                        SELECT id
                        FROM {" . $tempTableName . "}
                    )
            ");

            $cx->query("
                DELETE
                FROM {apb_sub}
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
        }

        $queryValues = array();

        // First build values and ensure the users don't do anything stupid
        foreach ($values as $key => $value) {
            switch ($key) {

                case Field::SUB_STATUS:
                    $queryValues['status'] = (int)$value;
                    break;

                case Field::SUB_ACTIVATED:
                    $queryValues['activated'] = (int)$value;
                    break;

                case Field::SUB_DEACTIVATED:
                    $queryValues['deactivated'] = (int)$value;
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
            ->update('apb_sub')
            ->fields($queryValues)
            ->condition('id', $select, 'IN')
            ->execute();

        $cx->query("DROP TABLE {" . $tempTableName . "}");
    }
}