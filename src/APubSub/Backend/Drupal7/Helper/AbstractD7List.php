<?php

namespace APubSub\Backend\Drupal7\Helper;

use APubSub\Backend\Drupal7\AbstractD7Object;
use APubSub\Backend\Drupal7\D7Context;
use APubSub\Helper\ListInterface;

/**
 * Drupal 7 base implementation of list helpers
 */
abstract class AbstractD7List extends AbstractD7Object implements
    \IteratorAggregate,
    ListInterface
{
    /**
     * Over this number of asked results objects will be single loaded on
     * demand while iterating instead of being all fully loaded
     *
     * FIXME: Restore this later
     */
    const LOAD_MULTIPLE_THREESHOLD = PHP_INT_MAX;

    /**
     * @var array
     */
    private $result;

    /**
     * @var \SelectQuery
     */
    private $query;

    /**
     * @var array
     */
    private $sorts = array();

    /**
     * @var int
     */
    private $limit = 20;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $count = null;

    /**
     * Get initial query (no limit, no offset, no sort):
     *   - The id field must be the first in the SELECT clause
     *   - All needed tables and JOIN statements must be set
     *
     * @return \SelectQuery Query
     */
    protected abstract function createdQuery();

    /**
     * Load single object instance
     *
     * @param mixed $id Identifier
     *
     * @return mixed    Object instance
     */
    protected abstract function loadObject($id);

    /**
     * Load list of objects instances
     *
     * @param array|Traversable $idList List of identifiers
     *
     * @return array|Traversable        List of object instances
     */
    protected abstract function loadObjects($idList);

    /**
     * Get sort column in the select query 
     *
     * @param int $sort Sort field
     */
    protected abstract function getSortColumn($sort);

    /**
     * Default constructor
     *
     * @param D7Context $context
     */
    final public function __construct(D7Context $context)
    {
        $this->context = $context;
    }

    /**
     * Get this query object
     */
    final protected function getQuery()
    {
        if (null === $this->query) {
            $this->query = $this->createdQuery();
        }

        return $this->query;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::addSort()
     */
    final public function addSort(
        $sort = ListInterface::SORT_FIELD_ID,
        $order = ListInterface::SORT_ORDER_ASC)
    {
        $allowed = $this->getAvailableSorts();

        if (!in_array($sort, $allowed)) {
            throw new \InvalidArgumentException("Sort field is not supported");
        }

        $this->sorts[$sort] = $direction;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::setLimit()
     */
    final public function setLimit($limit)
    {
        if (null !== $this->result) {
            throw new \LogicException("Query has already been run");
        }

        $this->limit = $limit;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::setOffset()
     */
    final public function setOffset($offset)
    {
        if (null !== $this->result) {
            throw new \LogicException("Query has already been run");
        }

        $this->offset = $offset;
    }

    /**
     * (non-PHPdoc)
     * @see \IteratorAggregate::getIterator()
     */
    final public function getIterator()
    {
        if (null === $this->result) {
            $query = $this->getQuery();
            $query->range($this->offset, $this->limit);

            foreach ($this->sorts as $sort => $order) {
                $query->orderBy(
                    $this->getSortColumn($sort),
                    ($order === ListInterface::SORT_ORDER_ASC ? 'asc' : 'desc'));
            }

            $result = $query->execute();

            if (!$this->limit || (self::LOAD_MULTIPLE_THREESHOLD < $this->limit)) {
                $this->result = new ApplyIteratorIterator($result, function ($object) {
                    return $this->loadObject($object->id);
                });
            } else {
                $this->result = $this->loadObjects($result->fetchCol());
            }
        }

        if (is_array($this->result)) {
            return new \ArrayIterator($this->result);
        } else {
            return $this->result;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    final public function count ()
    {
        if (null === $this->count) {
            $query = $this->getQuery();
            $query = clone $query;

            $this->count = $query
                ->countQuery()
                ->execute()
                ->fetchField();
        }

        return $this->count;
    }
}
