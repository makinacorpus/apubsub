<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\AbstractCursor;
use APubSub\BackendInterface;
use APubSub\CursorInterface;
use APubSub\Field;
use APubSub\Misc;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
abstract class AbstractD7Cursor extends AbstractCursor implements \IteratorAggregate
{
    /**
     * @var boolean
     */
    private $hasRun = false;

    /**
     * @var \SelectQuery
     */
    private $query;

    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $totalCount;

    /**
     * Internal conditions
     */
    private $conditions = array();

    /**
     * Default constructor
     *
     * @param BackendInterface $backend
     *   Backend
     * @param \QueryConditionInterface $query
     *   Message query
     */
    final public function __construct(D7Backend $backend)
    {
        parent::__construct($backend);

        $this->backend = $backend;
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
        if ($this->hasRun) {
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

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->hasRun = true;

        $result  = array();
        $query   = $this->getQuery();

        $this->applySorts($this->query, $this->getSorts());

        foreach ($query->execute() as $record) {
            yield $this->createObjectInstance($record);
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function count()
    {
        if (null !== $this->count) {
            return $this->count;
        }

        $query = clone $this->getQuery();

        return $this->count = (int)$query
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    final public function getTotalCount()
    {
        if (null !== $this->totalCount) {
            return $this->totalCount;
        }

        $query = clone $this->getQuery();

        return $this->totalCount = (int)$query
            ->range()
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * Build inititial query instance using the correct FROM and JOIN statements
     * ommiting the WHERE, ORDER, LIMIT and GROUP BY statements
     *
     * @return \SelectQueryInterface
     */
    protected abstract function buildQuery();

    /**
     * Apply the operator onto the query
     *
     * @param \SelectQueryInterface $query
     * @param string $statement
     * @param string $value
     */
    final protected function applyOperator(\SelectQueryInterface $query, $statement, $value)
    {
        // Check if $value contains an operator (i.e. if is associative array)
        if (is_array($value) && !Misc::isIndexed($value)) {

            // First key will be the operator
            $keys     = array_keys($value);
            $operator = $keys[0];
            $value    = $value[$operator];

            switch ($operator) {

                case '<>':
                    // FIXME I am sorry I am going to die because of this code...
                    if (is_array($value)) {
                        $query->condition(
                            db_or()
                                ->isNull($statement)
                                ->condition($statement, $value, 'NOT IN')
                        );
                    } else {
                        $query->condition(
                            db_or()
                                ->isNull($statement)
                                ->condition($statement, $value, '<>')
                        );
                    }
                    break;

                case 'exists':
                    $query->exists($value);
                    break;

                default:
                    $query->condition($statement, $value, $operator);
                    break;
            }
        } else if (null === $value) {
            $query->isNull($statement);
        } else {
            $query->condition($statement, $value);
        }
    }

    /**
     * Get query
     *
     * @return \SelectQueryInterface $query
     */
    final public function getQuery()
    {
        if (null === $this->query) {

            $this->query = $this->buildQuery();

            // Apply conditions.
            foreach ($this->conditions as $statement => $value) {
                $this->applyOperator($this->query, $statement, $value);
            }

            $limit = $this->getLimit();

            if (CursorInterface::LIMIT_NONE !== $limit) {
                $this->query->range($this->getOffset(), $limit);
            }
        }

        return $this->query;
    }
}
