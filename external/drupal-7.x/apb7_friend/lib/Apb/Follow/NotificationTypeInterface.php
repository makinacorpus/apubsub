<?php

namespace Apb\Follow;

interface NotificationTypeInterface
{
    /**
     * Get notification URI
     *
     * @param Notification $notification Notification for which to get the URI
     */
    public function getUri(Notification $notification);

    /**
     * Format notification as text, must not contain any link
     *
     * @param Notification $notification Notification to format
     *
     * @return string                    Formatted text
     */
    public function format(Notification $notification);

    /**
     * Get icon file URI
     *
     * @param Notification $notification Notification
     *
     * @return string                    Image URI
     */
    public function getImageURI(Notification $notification);
}
