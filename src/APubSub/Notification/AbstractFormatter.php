<?php

namespace APubSub\Notification;

/**
 * Abstract base implementation for formatter interface suitable for most needs
 */
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
        $this->type        = $type;
        $this->description = $description;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getType()
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getDescription()
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getImageURI()
     */
    public function getImageURI(Notification $notification)
    {
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getSubscriptionLabel()
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
