<?php

namespace Apb\Notification;

use Apb\Notification\Formatter\NullFormatter;

class TypeRegistry
{
    /**
     * Hook name fired by this implementation
     */
    const DRUPAL_HOOK_NAME = 'notifications_type_info';

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
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Overridable method for sub class that rebuilds the data array
     */
    protected function buildData()
    {
        $this->data = array();

        $hook = self::DRUPAL_HOOK_NAME;

        // Fetch module-driven definitions.
        foreach (module_implements($hook) as $module) {
            foreach (module_invoke($module, $hook) as $key => $info) {

                // Avoid duplicates and wild overrides.
                if (isset($types[$key])) {
                    watchdog('apb_follow', "Module @module overrides the @key notification type, dropping", array(
                        '@module' => $module,
                        '@key'    => $key,
                    ), WATCHDOG_WARNING);

                    continue;
                }

                if (!class_exists($info['class'])) {
                    watchdog('apb_follow', "Module @module provides @key notification type using unknown class @class, dropping", array(
                        '@module' => $module,
                        '@key'    => $key,
                        '@class'  => $info['class'],
                    ), WATCHDOG_WARNING);

                    continue;
                }

                /*
                if (!is_a($class, '\Apb\Follow\NotitificationTypeInterface')) {
                    watchdog('apb_follow', "Module @module provides @key type using class @class which does not implements \Apb\Follow\NotitificationTypeInterface, dropping", array(
                        '@module' => $module,
                        '@key'    => $key,
                        '@class'  => $class,
                    ), WATCHDOG_WARNING);

                    continue;
                }
                 */

                if (!isset($info['description'])) {
                    $info['description'] = $key;
                }

                $this->data[$key] = $info;
            }
        }

        // Allow other modules to alter definition (aKa "The Drupal Way").
        drupal_alter('apb_follow_type', $this->data);
    }

    /**
     * Refresh internal data and types definition
     */
    final public function refreshData()
    {
        $this->buildData();
    }

    /**
     * Get null implementation instance singleton
     *
     * @return \Apb\Notification\Formatter\NullFormatter
     */
    final private function getNullInstance()
    {
        if (null === $this->nullInstance) {
            $this->nullInstance = new NullFormatter();
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

        return new $class($type, $description);
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

        if (null === $this->data) {
            $this->refreshData();
        }

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
