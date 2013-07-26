<?php

namespace APubSub\Backend\Drupal7\Cursor;

use APubSub\Backend\AbstractCursor;
use APubSub\Backend\DefaultMessageInstance;
use APubSub\ContextInterface;
use APubSub\CursorInterface;
use APubSub\Field;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
class D7MessageCursor extends AbstractCursor implements \IteratorAggregate
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
     * @var boolean
     */
    private $queryOnSuber = false;

    /**
     * @var int
     */
    private $count;

    /**
     * Internal conditions
     */
    private $conditions = array();

    /**
     * @var boolean
     */
    private $distinct = true;

    /**
     * Default constructor
     *
     * @param ContextInterface $context    Context
     * @param \QueryConditionInterface $query Message query
     */
    public function __construct(ContextInterface $context)
    {
        parent::__construct($context);
    }

    /**
     * Toggle the distinct mode
     *
     * @param boolean $toggle
     */
    public function setDistinct($toggle = true)
    {
        $this->distinct = $toggle;
    }

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

                case Field::MSG_ID:
                    $this->conditions['q.msg_id'] = $value;
                    break;

                case Field::MSG_UNREAD:
                    $this->conditions['q.unread'] = $value;
                    break;

                case Field::MSG_QUEUE_ID:
                    $this->conditions['q.id'] = $value;
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

                    $this->conditions['m.type_id'] = $value;
                    break;

                case Field::SUB_ID:
                    $this->conditions['q.sub_id'] = $value;
                    break;

                case Field::SUBER_NAME:
                    $this->conditions['mp.name'] = $value;
                    $this->queryOnSuber = true;
                    break;


                case Field::CHAN_ID:
                    // FIXME: Find a better way
                    $chan = $this
                        ->context
                        ->getBackend()
                        ->getChannel($value);
                    $this->conditions['m.chan_id'] = $chan->getDatabaseId();
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

        $query = $this->getQuery();

        if (!$sorts = $this->getSorts()) {
            // Messages need a default ordering for fetching. If time for
            // more than one message is the same, ordering by message
            // identifier as second choice will lower unpredictable
            // behavior chances to happen (still possible thought since
            // serial fields don't guarantee order, even thought in real
            // life they do until very high values)
            $query
                ->orderBy('q.created', 'ASC')
                ->orderBy('q.msg_id', 'ASC');
        }

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

                case Field::SELF_ID:
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
            $query   = $this->getQuery();

            if (CursorInterface::LIMIT_NONE !== $limit) {
                $query->range($this->getOffset(), $limit);
            }

            $this->applySorts();

            foreach ($query->execute() as $record) {

                if ($record->read_timestamp) {
                    $readTime = (int)$record->read_timestamp;
                } else {
                    $readTime = null;
                }

                $result[] = new DefaultMessageInstance(
                    $this->context,
                    (string)$record->chan_id,
                    (int)$record->sub_id,
                    unserialize($record->contents),
                    (int)$record->id,
                    (int)$record->created,
                    $context->typeRegistry->getType($record->type_id),
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
            $query = clone $this->getQuery();

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
        if (null === $this->query) {

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

                $this->query = $this
                    ->context
                    ->dbConnection
                    ->select('apb_sub_map', 'mp');

                // @todo Smart conditions for subscriber and subscription
                $this->query
                    ->join('apb_queue', 'q', 'q.sub_id = mp.sub_id');
                $this->query
                    ->join('apb_msg', 'm', 'm.id = q.msg_id');
                $this->query
                    ->fields('m')
                    ->fields('q');
            } else {

              $this->query = $this
                  ->context
                  ->dbConnection
                  ->select('apb_queue', 'q');
              $this->query
                  ->join('apb_msg', 'm', 'm.id = q.msg_id');
              $this->query
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
                $this
                    ->query
                    ->groupBy('q.msg_id');
            }

            // Apply conditions.
            foreach ($this->conditions as $statement => $value) {
                $this->query->condition($statement, $value);
            }
        }

        return $this->query;
    }
}
