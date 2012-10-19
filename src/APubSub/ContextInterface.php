<?php

namespace APubSub;

/**
 * The object context is only here to generalize common technical helpers the
 * API should provide to all their objects, but without making it part of the
 * public user API
 */
interface ContextInterface
{
    /**
     * Backend the object is originating from
     *
     * @return \APubSub\PubSubInterface PubSub backend
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
}
