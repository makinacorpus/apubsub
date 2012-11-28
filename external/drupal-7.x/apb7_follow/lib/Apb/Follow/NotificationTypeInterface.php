<?php

namespace Apb\Follow;

interface NotificationTypeInterface extends IconSetAwareInterface
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
     * @return string                    Image URI, can also be an icon name
     *                                   from a stock icon set: difference to
     *                                   be determined with the lack of scheme
     *                                   in the returned URI
     */
    public function getImageURI(Notification $notification);
}
