<?php

namespace APubSub\Notification\ChanType;

use APubSub\Notification\ChanTypeInterface;

/**
 * Null implementation
 */
class NullChanType implements ChanTypeInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::getType()
     */
    public function getType()
    {
        return 'null';
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::getDescription()
     */
    public function getDescription()
    {
        return t("Null");
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::isVisible()
     */
    public function isVisible()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::getSubscriptionLabel()
     */
    public function getSubscriptionLabel($id)
    {
        return t('Unknown subscription with identifier %id', array(
            '%id' => $id,
        ));
    }
}
