<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\Notification\Formatter\NullFormatter;
use MakinaCorpus\APubSub\Notification\FormatterInterface;

/**
 * Formatter registry
 */
class FormatterRegistry
{
    /**
     * Stored known formatters
     *
     * @param array
     */
    private $data = array();

    /**
     * Stored known instances
     *
     * @var array
     */
    private $instances;

    /**
     * When in debug mode exceptions will be thrown
     *
     * @var bool
     */
    private $debug = false;

    /**
     * Null object implementation
     *
     * @var \MakinaCorpus\APubSub\Notification\FormatterInterface
     */
    private $nullInstance;

    public function __construct()
    {
        $this->nullInstance = new NullFormatter('null');
    }

    final public function setDebugMode($toggle = true)
    {
        $this->deubg = $toggle;
    }

    final public function typeExists($type)
    {
        return isset($this->data[$type]);
    }

    public function registerType($type, array $options = [])
    {
        if (isset($options['class']) && !class_exists($options['class'])) {
            throw new \InvalidArgumentException(sprintf(
                "Class '%s' does not exist", $options['class']));
        }

        if (isset($this->instances[$type])) {
            unset($this->instances[$type]);
        }

        $this->data[$type] = $options;

        return $this;
    }

    public function registerInstance(FormatterInterface $instance)
    {
        $type = $instance->getType();

        $this->data[$type] = array('class', get_class($instance));

        $this->instances[$type] = $instance;

        return $this;
    }

    protected function getInstanceFromData($type, $data)
    {
        $class = null;

        if (is_array($data)) {
            $class = $data['class'];
        } else if (is_string($data)) {
            $class = $data;
        } else {
            throw new \InvalidArgumentException(sprintf("Invalid data given for type '%s' does not exist", $type));
        }

        if (!class_exists($class)) {
            throw new \LogicException(sprintf("Class '%s' does not exist for type '%s'", $class, $type));
        }

        return new $class($type);
    }

    public function getInstance($type)
    {
        if (!isset($this->instances[$type])) {
            try {
                if (!isset($this->data[$type])) {
                    throw new \InvalidArgumentException(sprintf("Unknown type '%s'", $type));
                }

                $this->instances[$type] = $this->getInstanceFromData($type, $this->data[$type]);

            } catch (\Exception $e) {
                if ($this->debug) {
                    throw $e;
                } else {
                    $this->instances[$type] = $this->nullInstance;
                }
            }
        }

        return $this->instances[$type];
    }

    public function getAllInstances()
    {
        $ret = array();

        foreach ($this->data as $type => $data) {
            $ret[$type] = $this->getInstance($type);
        }

        return $ret;
    }
}
