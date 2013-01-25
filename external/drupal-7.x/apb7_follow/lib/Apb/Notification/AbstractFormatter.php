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

    /**
     * @var boolean
     */
    private $isVisible;

    public function __construct($type, $description, $isVisible = true)
    {
        $this->type        = $type;
        $this->description = $description;
        $this->isVisible   = $isVisible;
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

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::isVisible()
     */
    public function isVisible()
    {
        return $this->isVisible;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::getImageURI()
     */
    public function getImageURI(Notification $notification)
    {
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::getSubscriptionLabel()
     */
    public function getSubscriptionLabel($id)
    {
        if (null === $this->description) {
            return $this->type . ' #' . $id;
        } else {
            return $this->description. ' #' . $id;
        }
    }
}
