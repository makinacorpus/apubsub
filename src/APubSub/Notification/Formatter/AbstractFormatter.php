<?php

namespace APubSub\Notification\Formatter;

use APubSub\Notification\FormatterInterface;
use APubSub\Notification\Notification;
use APubSub\Notification\Registry\AbstractRegistryItem;

/**
 * Abstract base implementation for formatter interface suitable for most needs
 */
abstract class AbstractFormatter extends AbstractRegistryItem implements
    FormatterInterface
{
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
