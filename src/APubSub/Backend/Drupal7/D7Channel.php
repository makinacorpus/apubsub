<?php

namespace APubSub\Backend\Drupal7;

use APubSub\ChannelInterface;
use APubSub\ContextInterface;
use APubSub\Backend\DefaultChannel;
use APubSub\CursorInterface;

class D7Channel extends DefaultChannel implements ChannelInterface
{
    /**
     * Internal database identifier
     *
     * @var int
     */
    private $databaseId;

    /**
     * Default constructor
     *
     * @param int $databaseId   Internal database identifier
     * @param string $id        Channel identifier
     * @param ContextInterface  Context
     * @param int $creationTime Creation time UNIX timestamp
     * @param string $title     Human readable title
     */
    public function __construct($databaseId, $id, ContextInterface $context, $creationTime = null, $title = null)
    {
        parent::__construct($id, $context, $creationTime, $title);

        $this->databaseId = $databaseId;
    }

    /**
     * Get internal database identifier
     *
     * @return int Database identifier
     */
    final public function getDatabaseId()
    {
        return $this->databaseId;
    }
}
