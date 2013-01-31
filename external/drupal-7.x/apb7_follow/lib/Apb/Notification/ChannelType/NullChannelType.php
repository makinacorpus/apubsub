<?php

namespace Apb\Notification\ChannelType;

use Apb\Notification\ChannelTypeInterface;

/**
 * Null implementation
 */
class NullChannelType implements ChannelTypeInterface
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::getType()
     */
    public function getType()
    {
        return 'null';
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::getDescription()
     */
    public function getDescription()
    {
        return t("Null");
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::isVisible()
     */
    public function isVisible()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Notification\ChannelTypeInterface::getSubscriptionLabel()
     */
    public function getSubscriptionLabel($id)
    {
        return t('Unknown subscription with identifier %id', array(
            '%id' => $id,
        ));
    }
}
