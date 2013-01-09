<?php

namespace Apb\Notification\Formatter;

use Apb\Notification\Notification;
use Apb\Notification\FormatterInterface;

/**
 * Default null implementation
 */
class NullFormatter implements FormatterInterface
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::format()
     */
    public function format(Notification $notification)
    {
        return t("Something happened.");
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::getImageURI()
     */
    public function getImageURI(Notification $notification)
    {
        return null;
    }
}
