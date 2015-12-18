<?php

namespace MakinaCorpus\APubSub\Notification\Registry;

use MakinaCorpus\APubSub\Notification\RegistryInterface;
use MakinaCorpus\APubSub\Notification\RegistryItemInterface;

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
     * @var \MakinaCorpus\APubSub\Notification\FormatterInterface
     */
    private $nullInstance;

    /**
     * Known groups
     *
     * @var array
     */
    private $groups = array();

    final public function setDebugMode($toggle = true)
    {
        $this->deubg = $toggle;
    }

    final public function typeExists($type)
    {
        return isset($this->data[$type]);
    }

    public function registerType($type, array $options)
    {
        if (isset($options['class']) && !class_exists($options['class'])) {
            throw new \InvalidArgumentException(sprintf(
                "Class '%s' does not exist", $options['class']));
        }

        if (isset($this->instances[$type])) {
            unset($this->instances[$type]);
        }
        if (!isset($options['group'])) {
            $options['group'] = null;
        }

        $this->data[$type] = $options;

        // Auto registering the group if it does not exist, note that this may
        // lead to non human readable names being displayed, but is necessary
        // for consistency
        if (!isset($this->groups[$options['group']])) {
            $this->groups[$options['group']] = $options['group'];
        }

        return $this;
    }

    public function registerInstance(RegistryItemInterface $instance)
    {
        $type = $instance->getType();

        $this->data[$type] = array(
            'description' => $instance->getDescription(),
            'group'       => $instance->getGroupId(),
        );

        // Auto registering the group if it does not exist, note that this may
        // lead to non human readable names being displayed, but is necessary
        // for consistency
        if ($group = $instance->getGroupId() && !isset($this->groups[$group])) {
            $this->groups[$group] = $group;
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
     * Get default class for items
     *
     * @return string PHP class
     */
    abstract protected function getDefaultClass();

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
            if (isset($data['class'])) {
                $class   = $data['class'];
            } else {
                $class   = $this->getDefaultClass();
            }
            $description = $data['description'];
        } else if (is_string($data)) {
            $class       = $data;
            $description = $type;
        } else {
            throw new \InvalidArgumentException(sprintf(
                "Invalid data given for type '%s' does not exist", $type));
        }

        if (null === $class) {
            throw new \LogicException(sprintf(
                "Could not find a class for type '%s'", $type));
        }
        if (!class_exists($class)) {
            throw new \LogicException(sprintf(
                "Class '%s' does not exist for type '%s'", $class, $type));
        }

        return new $class(
            $type,
            $description,
            isset($data['group']) ? $data['group'] : null);
    }

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
     * Get known groups
     *
     * @return array Keys are group internal names, values are human readable
     *               labels
     */
    public function getGroups()
    {
        return $this->groups;
    }

    final public function getAllInstancesByGroup($group)
    {
        $ret = array();

        foreach ($this->data as $type => $data) {
            if (isset($data['group']) && $group === $data['group']) {
                $ret[$type] = $this->getInstance($type);
            }
        }

        return $ret;
    }

    final public function getAllInstances()
    {
        $ret = array();

        foreach ($this->data as $type => $data) {
            $ret[$type] = $this->getInstance($type);
        }

        return $ret;
    }
}
