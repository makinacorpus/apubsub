<?php

namespace APubSub\Backend;

use APubSub\Backend\AbstractObject;
use APubSub\ContextInterface;
use APubSub\CursorInterface;

/**
 * Generic implementation of \APubSub\CursorInterface based upon data
 * originating from an array. This is a very non efficient way to use
 * the API, please consider using this only if you have no choice.
 *
 * It originally has been developed for unit testing purposes.
 */
class ArrayCursor extends AbstractCursor implements
    \IteratorAggregate,
    CursorInterface
{
    /**
     * @var array
     */
    protected $array;

    /**
     * @var array
     */
    protected $availableSorts = array();

    /**
     * @var callable
     */
    protected $sortCallback;

    /**
     * Default constructor
     *
     * @param ContextInterface $context Context
     * @param array $array              Data to iterate over
     * @param array $availableSorts     List of available sort fields
     * @param callable $sortCallback    Sort callback that takes the array as
     *                                  first parameter and the result of the
     *                                  getSorts() method as second parameter
     */
    public function __construct(
        ContextInterface $context,
        array $array,
        array $availableSorts = null,
        $sortCallback = null)
    {
        $this->array = $array;

        if (null !== $sortCallback) {

            if (null === $availableSorts) {
                throw new \LogicException(
                    "Cannot sort without sort field definition");
            }

            if (!is_callable($sortCallback)) {
                throw new \LogicException(
                    "Given sort callback is not callable");
            }

            $this->sortCallback = $sortCallback;
            $this->availableSorts = $availableSorts;
        }

        parent::__construct($context);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return $this->availableSorts;
    }

    /**
     * (non-PHPdoc)
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator()
    {
        $this->setHasRun();

        if (null !== $this->sortCallback) {
            // Always run sorter even if we have no sorts. This class exists
            // for unit testing purpose, mainly, we can afford a non effective
            // function call here
            call_user_func_array($this->sortCallback, array(
                &$this->array,
                $this->getSorts(),
            ));
        }

        $iterator = new \ArrayIterator($this->array);
        $limit    = $this->getLimit();

        if (CursorInterface::LIMIT_NONE === $limit) {
            return new \LimitIterator($iterator, $this->getOffset());
        } else {
            return new \LimitIterator($iterator, $this->getOffset(), $limit);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    public function count()
    {
        return count($this->array);
    }
}
