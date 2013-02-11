<?php

namespace APubSub\Notification\Queue;

use APubSub\CursorInterface;
use APubSub\MessageInterface;
use APubSub\Notification\Queue\AbstractQueue;
use APubSub\Helper\MessageWorker;

/**
 * Queue using a CursorWorker instance
 *
 * This is the method you need to implement if you need to process each message
 * independently.
 */
abstract class AbstractWorkerQueue extends AbstractQueue
{
    /**
     * Process a single message
     *
     * @param MessageInterface $message Message
     */
    abstract protected function processSingle(MessageInterface $message);

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\QueueInterface::process()
     */
    final public function process(CursorInterface $cursor)
    {
        $worker = new MessageWorker($cursor, array($this, 'processSingle'));

        return $worker->process();
    }
}
