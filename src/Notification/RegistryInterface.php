<?php

namespace MakinaCorpus\APubSub\Notification;

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
     * @param RegistryItemInterface $instance Instance to register
     *
     * @return RegistryInterface              Self reference for chaining
     */
    public function registerInstance(RegistryItemInterface $instance);

    /**
     * Get instance
     *
     * @param string $type Type identifier
     *
     * @return mixed       Type instance
     */
    public function getInstance($type);

    /**
     * Get known groups
     *
     * @return array Keys are group internal names, values are human readable
     *               labels
     */
    public function getGroups();

    /**
     * Get all instances for the given group
     *
     * @param string $group            Internal group name
     *
     * @return RegistryItemInterface[] All know types instances keyed by type
     */
    public function getAllInstancesByGroup($group);

    /**
     * Get a list of all types instances
     *
     * Do not use this at runtime, only do it when necessary in administration
     * screens or whatever that will not be hit often
     *
     * @return RegistryItemInterface[] All know types instances keyed by type
     */
    public function getAllInstances();
}
