<?php

namespace Apb\Follow;

use APubSub\MessageInterface;

/**
 * Single notification is tied to its container: modification of its read
 * status must change the modified status of the container so that it can
 * be saved.
 */
class Notification
{
    /**
     * Contents from message.
     *
     * @var array
     */
    private $contents;

    /**
     * Contents contains the appropriate data
     *
     * @var bool
     */
    private $valid = false;

    /**
     * Build instance from message
     *
     * @param MessageInterface $message
     */
    public function __construct(MessageInterface $message)
    {
        if (($contents = $message->getContents()) &&
            is_array($contents) &&
            isset($contents['_i']) &&
            isset($contents['_t']))
        {
            $this->contents = $contents;
            $this->valid    = true;
        } else {
            $this->contents = array();
        }
    }

    /**
     * Tell if this instance has a valid message
     *
     * @return boolean True if valid
     */
    public function isValid()
    {
        return $this->valid;
    }

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
