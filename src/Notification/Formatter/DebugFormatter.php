<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\NotificationInterface;

/**
 * Restitutes various debug information from the notification
 */
class DebugFormatter implements FormatterInterface
{
    public function format(NotificationInterface $notification)
    {
        $ret = [
            'event' => $notification->getResourceType() . ':' . $notification->getAction(),
            'id'    => implode(', ', $notification->getResourceIdList()),
            'level' => $notification->getLevel(),
            'msg'   => $notification->getMessageId(),
        ];

        $text = [];
        foreach ($ret as $key => $value) {
            $text[] = '<strong>' . $key . '</strong>: ' . $value;
        }

        return implode('<br/>', $text);
    }

    public function getImageURI(NotificationInterface $notification)
    {
    }
}
