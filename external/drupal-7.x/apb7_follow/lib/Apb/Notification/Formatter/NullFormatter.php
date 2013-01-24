<?php

namespace Apb\Notification\Formatter;

use Apb\Notification\Notification;
use Apb\Notification\FormatterInterface;

/**
 * Null implementation
 */
class NullFormatter implements FormatterInterface
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::getType()
     */
    public function getType()
    {
        return 'null';
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::getDescription()
     */
    public function getDescription()
    {
        return t("Null");
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::isVisible()
     */
    public function isVisible()
    {
        // Not sure that true is a wise default
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::format()
     */
    public function format(Notification $notification)
    {
        return t("Something happened.");
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::getImageURI()
     */
    public function getImageURI(Notification $notification)
    {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\FormatterInterface::getSubscriptionLabel()
     */
    public function getSubscriptionLabel($id)
    {
        return t('Unknown subscription with identifier %id', array(
            '%id' => $id,
        ));
    }
}
