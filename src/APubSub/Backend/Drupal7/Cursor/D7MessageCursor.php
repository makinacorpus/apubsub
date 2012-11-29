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
            CursorInterface::FIELD_MSG_UNREAD,
            CursorInterface::FIELD_SUB_ID,
        );
    }

    /**
     * Get sort column in the select query 
     *
     * @param int $sort Sort field
     */
    protected function getSortColumn($sort)
    {
        switch ($sort)
        {
            case CursorInterface::FIELD_CHAN_ID:
                return 'm.chan_id';

            case CursorInterface::FIELD_MSG_ID:
                return 'q.msg_id';

            case CursorInterface::FIELD_MSG_SENT:
                return 'm.created';

            case CursorInterface::FIELD_MSG_UNREAD:
                return 'q.unread';

            case CursorInterface::FIELD_SELF_ID:
                return 'q.msg_id';

            case CursorInterface::FIELD_SUB_ID:
                return 'q.sub_id';

            default:
                throw new \InvalidArgumentException("Unsupported sort field");
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

            if (CursorInterface::LIMIT_NONE !== $limit) {
                $this->query->range($this->getOffset(), $limit);
            }

            if ($sorts = $this->getSorts()) {
                foreach ($sorts as $sort => $order) {
                    $this->query->orderBy(
                        $this->getSortColumn($sort),
                        ($order === CursorInterface::SORT_ASC ? 'asc' : 'desc'));
                }
            } else {
                // Messages need a default ordering for fetching. If time for
                // more than one message is the same, ordering by message
                // identifier as second choice will lower unpredictable
                // behavior chances to happen (still possible thought since
                // serial fields don't guarantee order, even thought in real
                // life they do until very high values)
                $this
                    ->query
                    ->orderBy('m.created', 'asc')
                        // FIXME: Need to duplicate created field into queue table
                        // for sorting: this will avoid filesort and ensure a const
                        // index in this query
                    ->orderBy('q.sub_id', 'asc');
            }

            $result = $this->query->execute();

            foreach ($result as $record) {
                $this->result[] = new DefaultMessage($this->context,
                    (string)$record->chan_id, (int)$record->sub_id,
                    unserialize($record->contents), (int)$record->id,
                    (int)$record->created, (bool)$record->unread);
            }
//echo "\n", $this->query, "\n\n";
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
