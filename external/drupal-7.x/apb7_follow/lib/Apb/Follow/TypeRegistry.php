<?php

namespace Apb\Follow;

use Apb\Follow\Notification\NullNotificationType;

class TypeRegistry
{
    /**
     * Hook name fired by this implementation
     */
    const DRUPAL_HOOK_NAME = 'apb7_follow_type_info';

    /**
     * Stored known formatters
     *
     * @param array
     */
    private $data;

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
    public function __construct($debug = true)
    {
        $this->debug = $debug;
    }

    /**
     * Overridable method for sub class that rebuilds the data array
     */
    protected function buildData()
    {
        $this->data = module_invoke_all(static::DRUPAL_HOOK_NAME);

        drupal_alter(static::DRUPAL_HOOK_NAME, $this->data);
    }

    final public function refreshData()
    {
        $this->buildData();
    }

    final private function getNullInstance()
    {
        if (null === $this->nullInstance) {
            $this->nullInstance = new NullNotificationType();
        }

        return $this->nullInstance;
    }

    /**
     * Overridable method that creates the real instance
     *
     * @param mixed $data Definition data
     */
    protected function getInstanceFromData($data)
    {
        if (is_string($data)) {
            if (!class_exists($data)) {
                throw new \LogicException(sprintf(
                    "Class '%s' does not exist", $data));
            }

            /*
            if (!is_subclass_of($data, '\Apb\Follow\NotificationTypeInterface')) {
                throw new \LogicException(sprintf("Class '%s' is not an '%s'",
                    $data, '\Apb\Follow\NotificationTypeInterface'));
            }
             */

            return new $data();
        }

        throw new \LogicException(
            "Cannot create instance from unknow input data");
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

            if (null === $this->data) {
                $this->refreshData();
            }

            try {
                if (!isset($this->data[$type])) {
                    throw new \InvalidArgumentException(sprintf(
                        "Unknown type '%s'", $type));
                }

                $this->instances[$type] = $this->getInstanceFromData($this->data[$type]);

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

    final public function getAllInstances()
    {
        $ret = array();

        if (null === $this->data) {
            $this->refreshData();
        }

        foreach ($this->data as $type => $data) {
            try {
                $ret[$type] = $this->getInstanceFromData($data);
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
