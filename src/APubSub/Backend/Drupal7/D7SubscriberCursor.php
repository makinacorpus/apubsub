<?php

namespace APubSub\Backend\Drupal7;

use APubSub\CursorInterface;
use APubSub\Field;
use APubSub\Misc;
use APubSub\Backend\DefaultSubscriber;

class D7SubscriberCursor extends AbstractD7Cursor
{
    /**
     * @var boolean
     */
    protected $queryOnChan = false;

    /**
     * {@inheritdoc}
     */
    public function getAvailableSorts()
    {
        return array(
            Field::SUBER_ACCESS,
            Field::SUBER_NAME,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function applyConditions(array $conditions)
    {
        $ret = array();

        foreach ($conditions as $field => $value) {
            switch ($field) {

                case Field::SUBER_ACCESS:
                    $ret['mp.accessed'] = $value;
                    break;

                case Field::SUBER_NAME:
                    $ret['mp.name'] = $value;
                    break;

                case Field::CHAN_ID:
                    $ret['c.name'] = $value;
                    $this->queryOnChan = true;
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
     * {@inheritdoc}
     */
    protected function applySorts(\SelectQueryInterface $query, array $sorts)
    {
        if (empty($sorts)) {
            $query->orderBy('mp.name', 'ASC');
        } else {
            foreach ($sorts as $sort => $order) {

                if ($order === CursorInterface::SORT_DESC) {
                    $direction = 'DESC';
                } else {
                    $direction = 'ASC';
                }

                switch ($sort)
                {
                    case Field::SUBER_ACCESS:
                        $query->orderBy('mp.accessed', $direction);
                        break;

                    case Field::SUBER_NAME:
                        $query->orderBy('mp.name', $direction);
                        break;

                    default:
                        throw new \InvalidArgumentException("Unsupported sort field");
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createObjectInstance(\stdClass $record)
    {
        // FIXME Data structures are not complete since we don't have the
        // subscriptions. Until now there is no use case we encountered
        // where we would need it when fetching subscribers from a cursor
        // but this could happen
        return new DefaultSubscriber(
            (string)$record->name,
            $this->getBackend(),
            [] // FIXME
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function buildQuery()
    {
        $query = $this
            ->getBackend()
            ->getConnection()
            ->select('apb_sub_map', 'mp')
            ->fields('mp')
            ->groupBy('mp.name')
        ;
        // FIXME
        // A MySQL GROUP_CONCAT() would have been perfect for fetching
        // subscriptions identifiers, sadly we must remain SQL standard
        // compliant to support all backends that Drupal supports.

        if ($this->queryOnChan) {
            $query->join('apb_sub', 's', "s.id = mp.sub_id");
            $query->join('apb_chan', 'c', "c.id = s.chan_id");
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $values)
    {
        throw new \Exception("You cannot update subscribers");
    }
}
