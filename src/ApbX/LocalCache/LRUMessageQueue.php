<?php

namespace ApbX\LocalCache;

use APubSub\MessageInterface;

class LRUMessageQueue implements \IteratorAggregate, MessageQueueInterface
{
    /**
     * List of \ApbX\LocalCache\QueueMessage instances
     *
     * @var array
     */
    protected $list = array();

    /**
     * Maximum queue size
     *
     * @var int
     */
    protected $limit;

    /**
     * Has this instance been modified during runtime
     *
     * @var bool
     */
    protected $modified = false;

    /**
     * Message class name to use instead of default QueueMessage class
     *
     * @var string
     */
    protected $messageClassName;

    /**
     * Default constructor
     *
     * @param number $limit            Maximum queue size
     * @param string $messageClassName Message class name to use for messages
     */
    public function __construct(
        $limit = MessageQueueInterface::NO_LIMIT,
        $messageClassName = null)
    {
        $this->setMaximumQueueSize($limit);

        if (null !== $messageClassName) {

            if (!class_exists($messageClassName)) {
                throw new \LogicException(sprintf(
                    "Class '%s' does not exist", $messageClassName));
            }
            if (!is_subclass_of($messageClassName, '\ApbX\LocalCache\QueueMessage')) {
                throw new \LogicException(sprintf(
                    "Class '%s' is not an '%s' instance",
                    $messageClassName, '\ApbX\LocalCache\QueueMessage'));
            }

            $this->messageClassName = $messageClassName;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::getMaximumQueueSize()
     */
    public function getMaximumQueueSize()
    {
        return $this->limit;
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::setMaximumQueueSize()
     */
    public function setMaximumQueueSize($limit, $doPurge = true)
    {
        $this->limit = $limit;

        if ($doPurge &&
            MessageQueueInterface::NO_LIMIT !== $this->limit
            && !empty($this->list))
        {
            $this->ensureLimit();
        }
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::isModified()
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::resetModifiedState()
     */
    public function resetModifiedState()
    {
        $this->modified = false;
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::setModified()
     */
    public function setModified()
    {
        $this->modified = true;
    }

    /**
     * Ensure given message has the right class
     *
     * @return \ApbX\LocalCache\QueueMessage New message with the right class
     */
    protected function ensureMessageType(MessageInterface $message)
    {
        if (!is_subclass_of($message, $this->messageClassName)) {
            $message = new $this->messageClassName($message, $this);
        } else {
            $message->setOwner($this);
        }

        return $message;
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::prepend()
     */
    public function prepend(MessageInterface $message)
    {
        array_unshift($this->list, $this->ensureMessageType($message));

        if (MessageQueueInterface::NO_LIMIT !== $this->limit && count($this->list) > $this->limit) {
            array_pop($this->list);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::prependAll()
     */
    public function prependAll($messages)
    {
        foreach ($messages as $message) {
            $this->prepend($message);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::append()
     */
    public function append(MessageInterface $message)
    {
        if (MessageQueueInterface::NO_LIMIT !== $this->limit && count($this->list) >= $this->limit) {
            return false;
        }

        $this->list[] = $this->ensureMessageType($message);

        return $this->modified = true;
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::appendAll()
     */
    public function appendAll($messages)
    {
        foreach ($messages as $message) {
            if (!$this->append($message)) {
                // Queue is full, do not continue
                break;
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::remove()
     */
    public function remove(MessageInterface $message)
    {
        foreach ($this->list as $index => $existing) {
            if ($existing->getId() === $message->getId()) {
                unset($this->list[$index]);

                $this->modified = true;
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::ensureLimit()
     */
    public function ensureLimit() 
    {
        if (MessageQueueInterface::NO_LIMIT === $this->limit) {
            return false;
        }

        $count = count($this->list);

        if ($this->limit < $count) {
            for ($i = $this->limit; $i < $count; ++$i) {
                array_pop($this->list);
            }

            return $this->modified = true;
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->list);
    }

    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->list);
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::sort()
     */
    public function sort(
        $field = MessageQueueInterface::SORT_CREATE_TIME,
        $order = MessageQueueInterface::SORT_DESC)
    {
        // FIXME: Implement this
        throw new \Exception("Not implemented yet");
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::countUnread()
     */
    public function countUnread()
    {
        $ret = 0;

        foreach ($this->list as $message) {
            if ($message->isUnread()) {
                ++$ret;
            }
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::hasUnread()
     */
    public function hasUnread()
    {
        foreach ($this->list as $message) {
            if ($message->isUnread()) {
                return true;
            }
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \ApbX\LocalCache\MessageQueueInterface::isEmpty()
     */
    public function isEmpty()
    {
        return empty($this->list);
    }

    /**
     * Ensures that all cached notifications knows their parent when
     * unserializing instances
     *
     * @see \ApbX\LocalCache\QueueMessage::__sleep()
     */
    public function __wakeUp()
    {
        foreach ($this->list as $message) {
            $message->setOwner($this);
        }
    }
}
