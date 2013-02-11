<?php

namespace APubSub\Notification\ChanType;

use APubSub\Notification\ChanTypeInterface;

/**
 * Default implementation
 */
class DefaultChanType implements ChanTypeInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $groupId;

    /**
     * @var boolean
     */
    private $isVisible;

    public function __construct($type, $description, $groupId = null, $isVisible = true)
    {
        $this->type        = $type;
        $this->description = $description;
        $this->groupId     = $groupId;
        $this->isVisible   = $isVisible;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::getType()
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::getDescription()
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getGroupId()
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::isVisible()
     */
    public function isVisible()
    {
        return $this->isVisible;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\ChanTypeInterface::getSubscriptionLabel()
     */
    public function getSubscriptionLabel($id)
    {
        if (null === $this->description) {
            return $this->type . ' #' . $id;
        } else {
            return $this->description. ' #' . $id;
        }
    }
}
