<?php

namespace APubSub\Helper;

use APubSub\CursorInterface;
use APubSub\MessageInterface;

/**
 * Queue cursor worker
 *
 * Walks over a complete cursor and applies a specific callback over each
 * found items. This can be useful for applying batches over sets of messages.
 */
class MessageWorker
{
    /**
     * Number of iterations to do whithout checking for ram or time limit
     */
    const BLIND_ITERATIONS_COUNT = 50;

    /**
     * @var callable
     */
    protected $workerCallback;

    /**
     * @var CursorInterface
     */
    protected $cursor;

    /**
     * @var boolean
     */
    protected $deleteOnConsume;

    /**
     * Default constructor
     *
     * @param CursorInterface $cursor  Cursor to work with. Limit and offset
     *                                 must be set for working through it
     * @param callable $workerCallback Callback that processes each item, this
     *                                 callback must take at least one parameter
     *                                 which will be the cursor returned item
     * @param boolean $deleteOnConsume Deletes the message once consumed
     */
    public function __construct(
        CursorInterface $cursor,
        $workerCallback,
        $deleteOnConsume = false)
    {
        if (is_callable($workerCallback)) {
            $this->workerCallback = $workerCallback;
        } else {
            throw new \InvalidArgumentException(
                "Given worker callback is not callable");
        }

        $this->cursor          = $cursor;
        $this->deleteOnConsume = $deleteOnConsume;
    }

    /**
     * Process a single item
     *
     * @return boolean True if an item was processed false if the cursor
     *                 limit has been reached
     */
    public function processSingle()
    {
        if ($message = next($this->cursor)) {
            if ($message instanceof MessageInterface) {

                call_user_func($this->workerCallback, $message);

                if ($this->deleteOnConsume) {
                    $message
                        ->getSubscription()
                        ->deleteMessage(
                              $message->getId());
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Process all items until memory or time limit has been reached
     *
     * @return boolean True if everything was processed, false if a system
     *                 limit was reached before it could end processing the
     *                 given cursor
     */
    public function process()
    {
        $memLimit = trim(ini_get('memory_limit'));
        if ($memLimit == '-1') {
            $memLimit = null;
        } else {
            if (!is_numeric($memLimit)) {
                switch (strtolower(substr($memLimit, -1))) {

                    case 'g':
                        // PHP magic cast to int will get rid of the unit
                        // character
                        $memLimit *= 1024;

                    case 'm':
                        $memLimit *= 1024;

                    case 'k':
                        $memLimit *= 1024;
                        break;

                    default:
                        $memLimit = null;
                        brea;
                }
            }
        }
        if (null !== $memLimit) {
            // Give a 15% security limit
            $memLimit = floor($memLimit * 0.85);
        }

        // Give a 20% security limit, consider the first 20% have been spent
        // to bootstrap the framework and gives us a comfortable security
        // margin
        $timeLimit = time() + floor(ini_get('max_execution_time') * 0.80);

        $i = 0;
        do {
            if (0 === (++$i % self::BLIND_ITERATIONS_COUNT)) {
                if ((null !== $memLimit && $memLimit < memory_get_usage()) ||
                    $timeLimit < time())
                {
                    return false;
                }
            }

            if (!$this->processSingle()) {
                return true;
            }
        } while (true);
    }
}
