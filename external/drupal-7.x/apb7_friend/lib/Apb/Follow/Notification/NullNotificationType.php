<?php

namespace Apb\Follow\Notification;

use Apb\Follow\Notification;
use Apb\Follow\NotificationTypeInterface;

/**
 * Default null implementation
 */
class NullNotificationType implements NotificationTypeInterface
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::getUri()
     */
    public function getUri(Notification $notification)
    {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::format()
     */
    public function format(Notification $notification)
    {
        return t("Someone done something");
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
