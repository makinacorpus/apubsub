<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\MessageContainerInterface;
use APubSub\CursorInterface;

/**
 * Default implementation of the message container interface that would fit most
 * objects for most backends
 */
abstract class AbstractMessageContainer extends AbstractObject implements
    MessageContainerInterface
{
    /**
     * Default conditions to force for each operation on this object
     *
     * @var array
     *   Key value pairs where keys are field names and values are either
     *   a single mixed value or an array of mixed values that represent
     *   the conditions
     */
    protected $invariant = array();

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param array $invariant          Default filters for cursors
     */
    public function __construct(ContextInterface $context, array $invariant = null)
    {
        parent::__construct($context);

        if (!empty($invariant)) {
            $this->invariant = $invariant;
        }
    }

    /**
     * Build conditions array
     *
     * @param array $conditions Previous conditions to override
     *
     * @return array            Conditions with invariant
     */
    final private function ensureConditions(array $conditions = null)
    {
        if (empty($conditions)) {
            return $this->invariant;
        }
        if (empty($this->invariant)) {
            return $conditions;
        }

        // FIXME: Should this throw an exception for conflicting keys?
        foreach ($this->invariant as $key => $value) {
            // foreach() loop is mandatory because array_merge() does not
            // preserve numeric keys
            $conditions[$key] = $value;
        }

        return $conditions;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::fetch()
     */
    public function fetch(array $conditions = null)
    {
        return $this
            ->context
            ->getBackend()
            ->fetch(
                $this->ensureConditions($conditions));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::flush()
     */
    public function flush()
    {
        return $this
            ->context
            ->getBackend()
            ->fetch(
                 $this->ensureConditions())
            ->delete();
    }
}
