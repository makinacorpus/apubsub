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
     * Default constructor
     *
     * @param string $type
     * @param string $description
     */
    public function __construct($type, $description)
    {
        $this->type        = $type;
        $this->description = $description;
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
}
