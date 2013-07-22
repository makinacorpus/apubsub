<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;
use APubSub\ContextInterface;

/**
 * Default context implementation
 */
class DefaultContext implements ContextInterface
{
    /**
     * @var BackendInterface
     */
    private $backend;

    /**
     * Key value pairs of options
     *
     * @var array
     */
    private $options = array();

    /**
     * Default constructor
     *
     * @param BackendInterface $backend  Backend
     * @param array|Traversable $options Options
     */
    public function __construct(BackendInterface $backend, $options = null)
    {
        $this->backend = $backend;

        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::getBackend()
     */
    final public function getBackend()
    {
        return $this->backend;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::setOptions()
     */
    public function setOptions($options)
    {
        if (is_array($options)) {
            $this->options = $options;
        } else if ($options instanceof \Traversable) {
            foreach ($options as $key => $value) {
                $this->options[$key] = $value;
            }
        } else {
            throw new \RuntimeException(
                sprintf("Options must be either an array or an instance of Traversable"));
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::getOptions()
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::has()
     */
    final public function has($key)
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::get()
     */
    final public function get($key)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::__get()
     */
    final public function __get($name)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\ContextInterface::__isset()
     */
    final public function __isset($name)
    {
        return array_key_exists($name, $this->options);
    }
}
