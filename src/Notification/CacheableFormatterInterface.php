<?php

namespace MakinaCorpus\APubSub\Notification;

/**
 * Notification formatter
 */
interface CacheableFormatterInterface extends FormatterInterface
{
    /**
     * Get additional data to precompute from this notification that needs to
     * be stored along
     *
     * When the format() method will be called, this data will be added into
     * the notification, and you can use it
     *
     * @return mixed[]
     *   Arbitrary set of key-value pairs that must not conflict with what is
     *   already stored into the message contents
     */
    public function prepareCache(NotificationInterface $notification);
}
