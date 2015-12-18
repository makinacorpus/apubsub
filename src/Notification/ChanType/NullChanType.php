<?php

namespace MakinaCorpus\APubSub\Notification\ChanType;

use MakinaCorpus\APubSub\Notification\ChanTypeInterface;

/**
 * Null implementation
 */
class NullChanType implements ChanTypeInterface
{
    public function getType()
    {
        return 'null';
    }

    public function getDescription()
    {
        return t("Null");
    }

    public function getGroupId()
    {
        return null;
    }

    public function isVisible()
    {
        return true;
    }

    public function getSubscriptionLabel($id)
    {
        return t('Unknown subscription with identifier %id', array(
            '%id' => $id,
        ));
    }
}
