<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractCursor;
use APubSub\ContextInterface;
use APubSub\CursorInterface;
use APubSub\Misc;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
abstract class AbstractD7Cursor extends AbstractCursor implements \IteratorAggregate
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
     * Internal conditions
     */
    private $conditions = array();

    /**
     * Default constructor
     *
     * @param ContextInterface $context    Context
     * @param \QueryConditionInterface $query Message query
     */
    final public function __construct(ContextInterface $context)
    {
        parent::__construct($context);
    }

    /**
     * Apply given sorts to the given query
     *
     * @param \SelectQueryInterface $query Query
     * @param array $sorts                 Sorts
     */
    abstract protected function applySorts(\SelectQueryInterface $query, array $sorts);

    /**
     * Apply conditions from the given input
     *
     * @param array $conditions Array of condition compatible with the fetch()
     *                          method of both Subscriber and Subscription
     */
    abstract protected function applyConditions(array $conditions);

    /**
     * Apply conditions
     *
     * @param array $conditions  Conditions
     *
     * @throws \RuntimeException If cursor already run
     */
    final public function setConditions(array $conditions)
    {
        if (null !== $this->iterator) {
            throw new \RuntimeException("Cursor query has already run");
        }

        $this->conditions = $this->applyConditions($conditions);
    }

    /**
     * Create target object from record
     *
     * @param \stdClass $record Result row from database query
     *
     * @return mixed            New object instance
     */
    abstract protected function createObjectInstance(\stdClass $record);

    public function getIterator()
    {
        if (null === $this->iterator) {

            $result  = array();
            $query   = $this->getQuery();

            $this->applySorts($this->query, $this->getSorts());

            foreach ($query->execute() as $record) {
                $result[] = $this->createObjectInstance($record);
            }

            $this->iterator = new \ArrayIterator($result);
        }

        return $this->iterator;
    }

    final public function count()
    {
        return count($this->getIterator());
    }

    final public function getTotalCount()
    {
        if (null === $this->count) {
            $this->count = (int)$this
                ->buildCountQuery()
                ->execute()
                ->fetchField();
        }

        return $this->count;
    }

    /**
     * Build inititial query instance using the correct FROM and JOIN statements
     * ommiting the WHERE, ORDER, LIMIT and GROUP BY statements
     *
     * @return \SelectQuery
     */
    protected abstract function buildQuery();

    /**
     * Build inititial COUNT query instance using the correct FROM and JOIN statements
     * ommiting the WHERE, ORDER, LIMIT and GROUP BY statements
     *
     * @return \SelectQuery
     */
    protected function buildCountQuery()
    {
        $query = clone $this->getQuery();

        return $query->range()->countQuery();
    }

    /**
     * Apply conditions on the given query
     *
     * If you override buildCountQuery() for example, please do not forget to
     * call this method on the generated count query.
     *
     * @param \SelectQueryInterface $query
     */
    final protected function applyConditionsOnQuery(\SelectQueryInterface $query)
    {
        // Apply conditions.
        foreach ($this->conditions as $statement => $value) {
            // Check if $value contains an operator (i.e. if is associative array)
            if (is_array($value) && !Misc::isIndexed($value)) {
                $keys = array_keys($value);
                $query->condition($statement, array_values($value), $keys[0]);
            } else {
                $query->condition($statement, $value);
            }
        }
    }

    /**
     * Get query
     *
     * @return \SelectQueryInterface
     */
    final public function getQuery()
    {
        if (null === $this->query) {

            $this->query = $this->buildQuery();
            $this->applyConditionsOnQuery($this->query);

            $limit = $this->getLimit();

            if (CursorInterface::LIMIT_NONE !== $limit) {
                $this->query->range($this->getOffset(), $limit);
            }
        }

        return $this->query;
    }
}
