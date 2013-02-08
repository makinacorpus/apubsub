<?php

namespace APubSub\Notification;

/**
 * Notification formatter
 */
interface RegistryItemInterface
{
    /**
     * Get internal type
     *
     * @return string
     */
    public function getType();

    /**
     * Get type description
     *
     * @return string Human readable string
     */
    public function getDescription();
}
