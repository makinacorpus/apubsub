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
     * @var array
     */
    private $result;

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
     * @param \SelectQueryInterface $query Message query
     */
    public function __construct(
        ContextInterface $context,
        \SelectQueryInterface $query)
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
            CursorInterface::FIELD_MSG_READ_TS,
            CursorInterface::FIELD_MSG_UNREAD,
            CursorInterface::FIELD_SUB_ID,
        );
    }

    /**
     * The queue table suffers from timestamp imprecision and message id serial
     * non predictability: we therefore need to apply multiple sorts at once in
     * most cases, which are specific to this table
     */
    protected function applySorts()
    {
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
        if (null === $this->result) {

            $this->result = array();
            $limit        = $this->getLimit();
            $context      = $this->getContext();

            if (CursorInterface::LIMIT_NONE !== $limit) {
                $this->query->range($this->getOffset(), $limit);
            }

            $this->applySorts();

            $result = $this->query->execute();

            foreach ($result as $record) {

                if ($record->read_timestamp) {
                    $readTime = (int)$record->read_timestamp;
                } else {
                    $readTime = null;
                }

                $this->result[] = new DefaultMessage(
                    $this->context,
                    (string)$record->chan_id,
                    (int)$record->sub_id,
                    unserialize($record->contents),
                    (int)$record->id,
                    (int)$record->created,
                    $context->typeHelper->getType($record->type_id),
                    (bool)$record->unread,
                    $readTime);
            }

            // We don't need this anymore
            unset($this->query);
        }

        return new \ArrayIterator($this->result);
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    final public function count ()
    {
        if (null === $this->count) {
            $query = clone $this->query;

            $this->count = $query
                ->countQuery()
                ->execute()
                ->fetchField();
        }

        return $this->count;
    }
}
