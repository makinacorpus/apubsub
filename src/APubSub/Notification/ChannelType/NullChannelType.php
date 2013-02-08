<?php

namespace APubSub\Notification\ChannelType;

use APubSub\Notification\ChannelTypeInterface;

/**
 * Null implementation
 */
class NullChannelType implements ChannelTypeInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChannelTypeInterface::getType()
     */
    public function getType()
    {
        return 'null';
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChannelTypeInterface::getDescription()
     */
    public function getDescription()
    {
        return t("Null");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChannelTypeInterface::isVisible()
     */
    public function isVisible()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChannelTypeInterface::getSubscriptionLabel()
     */
    public function getSubscriptionLabel($id)
    {
        return t('Unknown subscription with identifier %id', array(
            '%id' => $id,
        ));
    }
}
