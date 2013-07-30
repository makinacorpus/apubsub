<?php

namespace APubSub\Notification\Formatter;

use APubSub\Notification\Notification;
use APubSub\Notification\FormatterInterface;

/**
 * Null implementation
 */
class NullFormatter extends AbstractFormatter
{
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
