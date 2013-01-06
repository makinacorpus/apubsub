<?php

namespace Apb\Follow;

use StockIcon\IconThemeAware;

interface NotificationTypeInterface
{
    /**
     * Format notification as HTML code, if any link has to be set, set it
     * into this text
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
