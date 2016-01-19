<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\NotificationInterface;

/**
 * Null object implementation, used for fallback when debug is disabled
 */
class NullFormatter implements FormatterInterface
{
    public function format(NotificationInterface $notification)
    {
        return '';
    }

    public function getImageURI(NotificationInterface $notification)
    {
    }
}
