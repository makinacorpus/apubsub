<?php

namespace MakinaCorpus\APubSub\Notification\Registry;

use MakinaCorpus\APubSub\Notification\RegistryItemInterface;

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

    final public function getType()
    {
        return $this->type;
    }

    final public function getDescription()
    {
        return $this->description;
    }

    final public function getGroupId()
    {
        return $this->groupId;
    }
}
