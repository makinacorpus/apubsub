<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\NotificationInterface;

/**
 * Restitutes text, image and URI arbitrary set values in the data array
 */
class RawFormatter implements FormatterInterface
{
    public function format(NotificationInterface $notification)
    {
        return $notification['raw'];
    }

    public function getURI(NotificationInterface $notification)
    {
        return $notification['uri'];
    }

    public function getImageURI(NotificationInterface $notification)
    {
        return $notification['img'];
    }
}
