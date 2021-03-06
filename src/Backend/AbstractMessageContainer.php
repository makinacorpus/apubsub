<?php

namespace MakinaCorpus\APubSub\Backend;

use MakinaCorpus\APubSub\MessageContainerInterface;
use MakinaCorpus\APubSub\BackendInterface;

/**
 * Default implementation of the message container interface that would fit most
 * objects for most backends
 */
abstract class AbstractMessageContainer implements MessageContainerInterface
{
    use BackendAwareTrait;

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
     * @param BackendInterface $backend
     *   Backend
     * @param array $invariant
     *   Default filters for cursors
     */
    public function __construct(BackendInterface $backend, array $invariant = null)
    {
        $this->setBackend($backend);

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
     * {@inheritdoc}
     */
    public function fetch(array $conditions = null)
    {
        return $this->backend->fetch($this->ensureConditions($conditions));
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->backend->fetch($this->ensureConditions())->delete();
    }
}
