<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\Notification;

/**
 * Null implementation
 */
class NullFormatter extends AbstractFormatter
{
    public function format(Notification $notification)
    {
        return t("Something happened.");
    }
}
