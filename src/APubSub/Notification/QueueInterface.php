<?php

namespace APubSub\Notification;

use APubSub\CursorInterface;

/**
 * Process a message queue
 */
interface QueueInterface extends RegistryItemInterface
{
    /**
     * Process messages from the given cursor until its limit has been reached
     *
     * @param CursorInterface $cursor Messages cursor to process
     *
     * @return boolean                True if everything was processed, false
     *                                items remains
     */
    public function process(CursorInterface $cursor);
}
