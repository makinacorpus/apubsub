<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\MessageInstanceInterface;

/**
 * Base implementation for notification, you should probably never not use it
 */
final class DefaultNotification implements NotificationInterface
{
    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $resourceType = null;

    /**
     * @var int[]|string[]
     */
    private $resourceIdList = [];

    /**
     * @var int
     */
    private $level = NotificationInterface::LEVEL_INFO;

    /**
     * Default constructor
     *
     * @param MessageInstanceInterface $message
     */
    public function __construct(MessageInstanceInterface $message, FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        $this->message = $message;
        $this->level = $message->getLevel();

        list($this->resourceType, $this->action) = explode(':', $message->getType(), 2);

        if (($data = $message->getContents()) && is_array($data)) {
            if (isset($data['data'])) {
                $this->data = $data['data'];
            }
            if (isset($data['id'])) {
                $this->resourceIdList = $data['id'];
            }
        }
    }

    /**
     * {inheritdoc}
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * {inheritdoc}
     */
    public function getResourceIdList()
    {
        return $this->resourceIdList;
    }

    /**
     * {inheritdoc}
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * {inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {inheritdoc}
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * {inheritdoc}
     */
    public function getMessageId()
    {
        return $this->message->getId();
    }

    /**
     * {inheritdoc}
     */
    public function getURI()
    {
      return $this->formatter->getURI($this);
    }

    /**
     * {inheritdoc}
     */
    public function getImageURI()
    {
        return $this->formatter->getImageURI($this);
    }

    /**
     * {@inheritdoc}
     */
    public function format()
    {
        return $this->formatter->format($this);
    }

    /**
     * {inheritdoc}
     */
    public function getLevel()
    {
        return $this->message->getLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        // Avoid some PHP warnings, this is purely for display
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException("Notification are readonly");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException("Notification are readonly");
    }
}
