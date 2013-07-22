<?php

namespace APubSub;

/**
 * Backend dependent options container
 */
interface ContextInterface
{
    /**
     * Backend the object is tied to
     *
     * @return \APubSub\BackendInterface PubSub backend
     */
    public function getBackend();

    /**
     * Set options, this should be used for internal mostly, changing options
     * at runtime is a dangerous thing to do
     *
     * @param array|Traversable $options Options to set
     */
    public function setOptions($options);

    /**
     * Get options that have been used for backend initialization
     *
     * @return array|Traversable Options
     */
    public function getOptions();

    /**
     * Does this key exists
     *
     * @param string $key Key
     *
     * @return boolean    True if key exists
     */
    public function has($key);

    /**
     * Get single option value
     *
     * @param string $key    Key
     *
     * @return mixed         Variable value
     */
    public function get($key);

    /**
     * Alias of get()
     *
     * @param string $key    Key
     * @param mixed $default Default value
     *
     * @return mixed         Variable value
     */
    public function __get($name);

    /**
     * Alias of has()
     *
     * @param string $key Key
     *
     * @return boolean    True if key exists
     */
    public function __isset($name);
}
