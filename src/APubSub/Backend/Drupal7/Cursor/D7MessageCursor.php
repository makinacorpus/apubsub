<?php

namespace APubSub\Backend\Drupal7\Cursor;

use APubSub\Backend\AbstractCursor;
use APubSub\Backend\DefaultMessage;
use APubSub\ContextInterface;
use APubSub\CursorInterface;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
class D7MessageCursor extends AbstractCursor implements
    \IteratorAggregate,
    CursorInterface
{
    /**
     * @var \ArrayIterator
     */
    private $iterator;

    /**
     * @var \SelectQuery
     */
    private $query;

    /**
     * @var int
     */
    private $count;

    /**
     * Default constructor
     *
     * @param ContextInterface $context    Context
     * @param \QueryConditionInterface $query Message query
     */
    public function __construct(
        ContextInterface $context,
        \QueryConditionInterface $query)
    {
        $this->query = $query;

        parent::__construct($context);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            CursorInterface::FIELD_CHAN_ID,
            CursorInterface::FIELD_MSG_ID,
            CursorInterface::FIELD_MSG_SENT,
            CursorInterface::FIELD_MSG_TYPE,
            CursorInterface::FIELD_MSG_LEVEL,
            CursorInterface::FIELD_MSG_READ_TS,
            CursorInterface::FIELD_MSG_UNREAD,
            CursorInterface::FIELD_SUB_ID,
        );
    }

    /**
     * Apply conditions from the given input
     *
     * @param array $conditions Array of condition compatible with the fetch()
     *                          method of both Subscriber and Subscription
     */
    public function applyConditions(array $conditions)
    {
        if (null !== $this->iterator) {
            throw new \LogicException("Cursor already run");
        }

        foreach ($conditions as $field => $value) {
            switch ($field) {

                case CursorInterface::FIELD_MSG_ID:
                    $this->query->condition('q.msg_id', $value);
                    break;

                case CursorInterface::FIELD_MSG_UNREAD:
                    $this->query->condition('q.unread', $value);
                    break;

                case CursorInterface::FIELD_MSG_TYPE:

                    $typeHelper = $this->getContext()->typeHelper;

                    if (is_array($value)) {
                        array_walk($value, function (&$value) use ($typeHelper) {
                            $value = $typeHelper->getTypeId($value);
                        });
                    } else {
                        $value = $typeHelper->getTypeId($value);
                    }

                    $this->query->condition('m.type_id', $value);
                    break;

                case CursorInterface::FIELD_SUB_ID:
                    $this->query->condition('q.sub_id', $value);
                    break;

                default:
                    trigger_error(sprintf("% does not support filter %d yet",
                        get_class($this), $field));
                    break;
            }
        }
    }

    /**
     * The queue table suffers from timestamp imprecision and message id serial
     * non predictability: we therefore need to apply multiple sorts at once in
     * most cases, which are specific to this table
     */
    protected function applySorts()
    {
        if (null !== $this->iterator) {
            throw new \LogicException("Cursor already run");
        }

        if (!$sorts = $this->getSorts()) {
            // Messages need a default ordering for fetching. If time for
            // more than one message is the same, ordering by message
            // identifier as second choice will lower unpredictable
            // behavior chances to happen (still possible thought since
            // serial fields don't guarantee order, even thought in real
            // life they do until very high values)
            $this
                ->query
                ->orderBy('q.created', 'ASC')
                ->orderBy('q.msg_id', 'ASC');
        }

        foreach ($this->getSorts() as $sort => $order) {

            if ($order === CursorInterface::SORT_DESC) {
                $direction = 'DESC';
            } else {
                $direction = 'ASC';
            }

            switch ($sort)
            {
                case CursorInterface::FIELD_CHAN_ID:
                    $this->query->orderBy('m.chan_id', $direction);
                    break;

                case CursorInterface::FIELD_SELF_ID:
                case CursorInterface::FIELD_MSG_ID:
                case CursorInterface::FIELD_MSG_SENT:
                    $this
                        ->query
                        ->orderBy('q.created', $direction)
                        ->orderBy('q.msg_id', $direction);
                    break;

                case CursorInterface::FIELD_MSG_TYPE:
                    $this->query->orderBy('m.type', $direction);
                    break;

                case CursorInterface::FIELD_MSG_READ_TS:
                    $this->query->orderBy('m.read_timestamp', $direction);
                    break;

                case CursorInterface::FIELD_MSG_UNREAD:
                    $this->query->orderBy('q.msg_id', $direction);
                    break;

                case CursorInterface::FIELD_MSG_LEVEL:
                    $this->query->orderBy('m.level', $direction);
                    break;

                case CursorInterface::FIELD_SUB_ID:
                    $this->query->orderBy('q.sub_id', $direction);
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported sort field");
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \IteratorAggregate::getIterator()
     */
    final public function getIterator()
    {
        if (null === $this->iterator) {

            $result  = array();
            $limit   = $this->getLimit();
            $context = $this->getContext();

            if (CursorInterface::LIMIT_NONE !== $limit) {
                $this->query->range($this->getOffset(), $limit);
            }

            $this->applySorts();

            foreach ($this->query->execute() as $record) {

                if ($record->read_timestamp) {
                    $readTime = (int)$record->read_timestamp;
                } else {
                    $readTime = null;
                }

                $result[] = new DefaultMessage(
                    $this->context,
                    (string)$record->chan_id,
                    (int)$record->sub_id,
                    unserialize($record->contents),
                    (int)$record->id,
                    (int)$record->created,
                    $context->typeHelper->getType($record->type_id),
                    (bool)$record->unread,
                    $readTime,
                    (int)$record->level);
            }

            $this->iterator = new \ArrayIterator($result);
        }

        return $this->iterator;
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    final public function count()
    {
        return count($this->getIterator());
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    final public function getTotalCount()
    {
        if (null === $this->count) {
            $query = clone $this->query;

            $this->count = $query
                ->range()
                ->countQuery()
                ->execute()
                ->fetchField();
        }

        return $this->count;
    }

    /**
     * Get query
     *
     * @return \SelectQueryInterface $query
     */
    final public function getQuery()
    {
        return $this->query;
    }
}
