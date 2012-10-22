<?php

namespace Apb\Follow;

use APubSub\MessageInterface;

class Notification
{
    /**
     * Has this notification been read
     *
     * @var bool
     */
    private $unread = true;

    /**
     * Inner message
     *
     * @var \APubSub\MessageInterface
     */
    private $message;

    /**
     * Default constructor
     *
     * @param MessageInterface $message Inner message
     * @param bool $unread              Read status
     */
    public function __construct(MessageInterface $message, $unread = true)
    {
        $this->message = $message;
        $this->unread = true;
    }

    /**
     * Get inner message
     *
     * @return \APubSub\MessageInterface Inner message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Has this instance been read
     *
     * @return bool True if this instance has not been read
     */
    public function isUnread()
    {
        return $this->unread;
    }
}
