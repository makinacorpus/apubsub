<?php

namespace Apb\Notification;

abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $description;

    public function __construct($type, $description)
    {
        $this->type = $type;
        $this->description = $description;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::getType()
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::getDescription()
     */
    public function getDescription()
    {
        return $this->description;
    }
}
