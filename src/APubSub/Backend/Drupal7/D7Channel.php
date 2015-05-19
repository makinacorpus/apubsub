<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultChannel;
use APubSub\BackendInterface;
use APubSub\ChannelInterface;
use APubSub\CursorInterface;

class D7Channel extends DefaultChannel
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
     * @param int $databaseId
     *   Internal database identifier
     * @param string $id
     *   Channel identifier
     * @param BackendInterface $backend
     *   Backend
     * @param \DateTime $creationTime
     *   Creation date
     * @param string $title
     *   Human readable title
     */
    public function __construct($databaseId, $id, BackendInterface $backend, \DateTime $creationTime = null, $title = null)
    {
        parent::__construct($id, $backend, $creationTime, $title);

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
