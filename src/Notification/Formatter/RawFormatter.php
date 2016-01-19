<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\NotificationInterface;

/**
 * Restitutes text given in the 'raw' key
 */
class RawFormatter implements FormatterInterface
{
    public function format(NotificationInterface $notification)
    {
        return $notification['raw'];
    }

    public function getImageURI(NotificationInterface $notification)
    {
    }
}
