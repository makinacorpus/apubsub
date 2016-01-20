<?php

namespace MakinaCorpus\APubSub\Notification;

/**
 * Notification formatter
 */
interface FormatterInterface
{
    /**
     * Format notification as HTML code, if any link has to be set, set it
     * into this text
     *
     * @param NotificationInterface $notification
     *   Notification to format
     *
     * @return string
     *   Formatted text
     */
    public function format(NotificationInterface $notification);

    /**
     * Get action URI
     *
     * @param NotificationInterface $notification
     *   Notification
     *
     * @return string
     *   URI or null if no revelant
     */
    public function getURI(NotificationInterface $notification);

    /**
     * Get icon file URI
     *
     * @param NotificationInterface $notification
     *   Notification
     *
     * @return string
     *   Image URI or null if none
     */
    public function getImageURI(NotificationInterface $notification);
}
