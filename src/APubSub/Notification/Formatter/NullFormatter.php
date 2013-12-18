<?php

namespace APubSub\Notification\Formatter;

use APubSub\Notification\Notification;
use APubSub\Notification\FormatterInterface;

/**
 * Null implementation
 */
class NullFormatter extends AbstractFormatter
{
    public function format(Notification $notification)
    {
        return t("Something happened.");
    }

    public function getImageURI(Notification $notification)
    {
        return null;
    }
}
