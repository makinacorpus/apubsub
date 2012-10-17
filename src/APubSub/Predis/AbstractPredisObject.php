<?php

namespace APubSub\Predis;

/**
 * Base implementation for Predis objects
 */
abstract class AbstractPredisObject
{
    /**
     * @var \APubSub\Predis\PredisContext
     */
    protected $context;

    /**
     * Set database connection
     *
     * @param PredisContext $context Context
     */
    public function setContext(PredisContext $context)
    {
        if (null !== $this->context) {
            throw new \LogicException("Context cannot be unset");
        }

        $this->context = $context;
    }

    /**
     * Get database connection
     *
     * @return \APubSub\Predis\PredisContext Context
    */
    public function getContext()
    {
        if (null === $this->context) {
            throw new \LogicException("Context is not set");
        }

        return $this->context;
    }
}
