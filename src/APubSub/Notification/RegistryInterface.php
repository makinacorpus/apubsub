<?php

namespace APubSub\Notification;

use APubSub\Notification\Formatter\NullFormatter;

/**
 * Simple component registry
 */
interface RegistryInterface
{
    /**
     * Set debug mode
     *
     * Debug mode on will never return null instances but throw exceptions
     * instead, useful for development phase.
     *
     * @param boolean $toggle Set to true to enable false to disable
     */
    public function setDebugMode($toggle = true);

    /**
     * Tell if the given type exists
     *
     * @param string $type Type
     *
     * @return boolean     True if type exists false otherwise
     */
    public function typeExists($type);

    /**
     * Register single type
     *
     * Existing definition will be overriden
     *
     * @param string $type       Type
     * @param array  $options    Type options, must contain at least 'class'
     *                           and 'description' keys
     *
     * @return RegistryInterface Self reference for chaining
     */
    public function registerType($type, array $options);

    /**
     * Register single formatter instance
     *
     * Existing definition will be overriden
     *
     * @param mixed $instance    Instance to register
     *
     * @return RegistryInterface Self reference for chaining
     */
    public function registerInstance($instance);

    /**
     * Get instance
     *
     * @param string $type Type identifier
     *
     * @return mixed       Type instance
     */
    public function getInstance($type);

    /**
     * Get a list of all types instances
     *
     * Do not use this at runtime, only do it when necessary in administration
     * screens or whatever that will not be hit often
     *
     * @return array All know types instances
     */
    public function getAllInstances();
}
