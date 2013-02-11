<?php

namespace APubSub\Notification\Registry;

use APubSub\Notification\RegistryItemInterface;

abstract class AbstractRegistryItem implements RegistryItemInterface
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
     * Default constructor
     *
     * @param string $type
     * @param string $description
     */
    public function __construct($type, $description, $groupId = null)
    {
        $this->type        = $type;
        $this->description = $description;
        $this->groupId     = $groupId;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getType()
     */
    final public function getType()
    {
        return $this->type;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getDescription()
     */
    final public function getDescription()
    {
        return $this->description;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\RegistryItemInterface::getGroupId()
     */
    final public function getGroupId()
    {
        return $this->groupId;
    }
}
