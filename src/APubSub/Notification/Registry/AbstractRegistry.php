<?php

namespace APubSub\Notification\Registry;

use APubSub\Notification\RegistryInterface;

/**
 * Base implementation
 */
abstract class AbstractRegistry implements RegistryInterface
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
     * @var \APubSub\Notification\FormatterInterface
     */
    private $nullInstance;

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryInterface::setDebugMode()
     */
    final public function setDebugMode($toggle = true)
    {
        $this->deubg = $toggle;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryInterface::typeExists()
     */
    final public function typeExists($type)
    {
        return isset($this->data[$type]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryInterface::registerType()
     */
    public function registerType($type, array $options)
    {
        if (!isset($options['class'])) {
            throw new \InvalidArgumentException(sprintf("Class is not set"));
        }
        if (!class_exists($options['class'])) {
            throw new \InvalidArgumentException(sprintf(
                "Class '%s' does not exist", $options['class']));
        }

        if (isset($this->instances[$type])) {
            unset($this->instances[$type]);
        }

        $this->data[$type] = $options;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryInterface::registerInstance()
     */
    public function registerInstance($instance)
    {
        $type = $instance->getType();

        if (isset($this->data[$type])) {
            unset($this->data[$type]);
        }

        $this->instances[$type] = $instance;

        return $this;
    }

    /**
     * Get null implementation instance singleton
     *
     * @return mixed Null instance
     */
    final private function getNullInstance()
    {
        if (null === $this->nullInstance) {
            $this->nullInstance = $this->createNullInstance();
        }

        return $this->nullInstance;
    }

    /**
     * Get null implementation instance singleton
     *
     * @return mixed Null instance
     */
    abstract protected function createNullInstance();

    /**
     * Overridable method that creates the real instance
     *
     * @param mixed $data Definition data
     */
    protected function getInstanceFromData($type, $data)
    {
        $class       = null;
        $description = null;

        if (is_array($data)) {
            $class       = $data['class'];
            $description = $data['description'];
        } else if (is_string($data)) {
            $class       = $data;
            $description = $type;
        } else {
            throw new \InvalidArgumentException(sprintf(
                "Invalid data given for type '%s' does not exist", $type));
        }

        if (!class_exists($class)) {
            throw new \LogicException(sprintf(
                "Class '%s' does not exist for type '%s'", $class, $type));
        }

        return new $class($type, $description);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryInterface::getInstance()
     */
    final public function getInstance($type)
    {
        if (!isset($this->instances[$type])) {
            try {
                if (!isset($this->data[$type])) {
                    throw new \InvalidArgumentException(sprintf(
                        "Unknown type '%s'", $type));
                }

                $this->instances[$type] = $this->getInstanceFromData($type, $this->data[$type]);

            } catch (\Exception $e) {
                if ($this->debug) {
                    throw $e;
                } else {
                    $this->instances[$type] = $this->getNullInstance();
                }
            }
        }

        return $this->instances[$type];
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryInterface::getAllInstances()
     */
    final public function getAllInstances()
    {
        $ret = array();

        foreach ($this->data as $type => $data) {
            $ret[$type] = $this->getInstance($type);
        }

        return $ret;
    }
}
