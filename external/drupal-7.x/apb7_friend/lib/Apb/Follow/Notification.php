<?php

namespace Apb\Follow;

use ApbX\LocalCache\QueueMessage;

/**
 * Single notification is tied to its container: modification of its read
 * status must change the modified status of the container so that it can
 * be saved.
 */
class Notification extends QueueMessage
{
    /**
     * Get arbitrary set data when message was sent
     *
     * @return mixed Arbitrary set data
     */
    public function getData()
    {
        return $this->contents;
    }

    /**
     * Get notification source identifier
     *
     * @return mixed Arbitrary source identifier
     */
    public function getSourceId()
    {
        return $this->contents['_i'];
    }

    /**
     * Get notification type
     *
     * @return string Notification type
     */
    public function getType()
    {
        return $this->contents['_t'];
    }

    /**
     * Get arbitrary value from arbitrary data
     *
     * @param string $key Value key
     */
    public function get($key)
    {
        return isset($this->contents[$key]) ? $this->contents[$key] : null;
    }
}
