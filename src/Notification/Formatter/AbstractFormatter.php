<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\Notification;
use MakinaCorpus\APubSub\Notification\Registry\AbstractRegistryItem;

/**
 * Abstract base implementation for formatter interface suitable for most needs
 */
abstract class AbstractFormatter extends AbstractRegistryItem implements
    FormatterInterface
{
    public function getImageURI(Notification $notification)
    {
    }

    public function getSubscriptionLabel($id)
    {
        if (null === $this->description) {
            return $this->type . ' #' . $id;
        } else {
            return $this->description. ' #' . $id;
        }
    }
}
