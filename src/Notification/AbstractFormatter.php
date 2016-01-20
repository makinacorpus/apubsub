<?php

namespace MakinaCorpus\APubSub\Notification;

/**
 * Base implementation that leaves only the format() method to implement
 */
abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * {inheritdoc}
     */
    public function getURI(NotificationInterface $notification)
    {
        return null;
    }

    /**
     * {inheritdoc}
     */
    public function getImageURI(NotificationInterface $notification)
    {
        return null;
    }
}
