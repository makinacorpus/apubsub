<?php

namespace APubSub\Notification\Formatter;

use APubSub\Notification\Notification;
use APubSub\Notification\FormatterInterface;

/**
 * Null implementation
 */
class NullFormatter implements FormatterInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getType()
     */
    public function getType()
    {
        return 'null';
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\FormatterInterface::getDescription()
     */
    public function getDescription()
    {
        return t("Null");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getGroupId()
     */
    public function getGroupId()
    {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Follow\NotificationTypeInterface::format()
     */
    public function format(Notification $notification)
    {
        return t("Something happened.");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Follow\NotificationTypeInterface::getImageURI()
     */
    public function getImageURI(Notification $notification)
    {
        return null;
    }
}
