<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\NotificationInterface;

class NullFormatter implements FormatterInterface
{
    public function format(NotificationInterface $notification)
    {
        return '';
    }

    public function getURI(NotificationInterface $notification)
    {
    }

    public function getImageURI(NotificationInterface $notification)
    {
    }
}
