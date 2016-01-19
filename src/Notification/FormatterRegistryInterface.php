<?php

namespace MakinaCorpus\APubSub\Notification;

interface FormatterRegistryInterface
{
    /**
     * Set or remove debug mode
     *
     * @param boolean $toggle
     */
    public function setDebugMode($toggle = true);

    /**
     * Does type exist
     *
     * @param string $type
     *
     * @return boolean
     */
    public function typeExists($type);

    /**
     * Register type by its class name or service identifier
     *
     * @param string $type
     * @param string $className
     *
     * @return FormatterRegistryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function registerType($type, $className);

    /**
     * Register already created instance
     *
     * @param string $type
     * @param FormatterInterface $instance
     *
     * @return FormatterRegistryInterface
     */
    public function registerInstance($type, FormatterInterface $instance);

    /**
     * Get instance
     *
     * @param string $type
     *
     * @return FormatterInterface
     *
     * @throws \InvalidArgumentException
     */
    public function get($type);

    /**
     * Get all registered type list
     *
     * @return string[]
     */
    public function getTypeList();
}
