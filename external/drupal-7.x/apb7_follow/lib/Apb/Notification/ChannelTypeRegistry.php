<?php

namespace Apb\Notification;

use Apb\Notification\ChannelType\NullChannelType;

/**
 * FIXME: Redundancies between FormatterRegistry and ChannelTypeRegistry
 */
class ChannelTypeRegistry
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
     * @var \Apb\Follow\NotificationTypeInterface
     */
    private $nullInstance;

    /**
     * Default constructor
     *
     * @param string $debug Debug mode toggle, true to enable
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Tell if the given type exists
     *
     * @param string $type Type
     *
     * @return boolean     True if type exists false otherwise
     */
    public function typeExists($type)
    {
        return isset($this->data[$type]);
    }

    /**
     * Register single type
     *
     * Existing definition will be overriden
     *
     * @param string $type        Type
     * @param string $class       Class to use
     * @param string $description Human readable description
     * @param string $isVisible   Visible state for this type of notification
     */
    public function registerType($type, $class, $description = null, $isVisible = true)
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf(
                "Class '%s' does not exist", $class));
        }

        if (isset($this->instances[$type])) {
            unset($this->instances[$type]);
        }

        $this->data[$type] = array(
            'class'       => $class,
            'description' => $description,
            'visible'     => $isVisible,
        );
    }

    /**
     * Register single formatter instance
     *
     * Existing definition will be overriden
     *
     * @param ChannelTypeInterface $instance Instance to register
     */
    public function registerInstance(ChannelTypeInterface $instance)
    {
        $type = $instance->getType();

        if (isset($this->data[$type])) {
            unset($this->data[$type]);
        }

        $this->instances[$type] = $instance;
    }

    /**
     * Get null implementation instance singleton
     *
     * @return \Apb\Notification\Formatter\NullFormatter
     */
    final private function getNullInstance()
    {
        if (null === $this->nullInstance) {
            $this->nullInstance = new NullChannelType();
        }

        return $this->nullInstance;
    }

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

        return new $class($type, $description, $data['visible']);
    }

    /**
     * Get instance
     *
     * @param string $type                           Type identifier
     *
     * @return \Apb\Follow\NotificationTypeInterface Type instance
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
     * Get a list of all types instances
     *
     * Do not use this at runtime, only do it when necessary in administration
     * screens or whatever that will not be hit often
     *
     * @return \Apb\Follow\NotificationTypeInterface[] All know types instances
     */
    final public function getAllInstances()
    {
        $ret = array();

        foreach ($this->data as $type => $data) {
            try {
                $ret[$type] = $this->getInstanceFromData($type, $data);
            } catch (\Exception $e) {
                if ($this->debug) {
                    throw $e;
                } else {
                    $ret[$type] = $this->getNullInstance();
                }
            }
        }

        return $ret;
    }
}
