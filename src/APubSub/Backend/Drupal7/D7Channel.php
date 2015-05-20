<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Backend\DefaultChannel;
use APubSub\BackendInterface;

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
     * @param \DateTime $createdAt
     *   Creation date
     * @param \DateTime $updatedAt
     *   Update date
     * @param string $title
     *   Human readable title
     */
    public function __construct($databaseId, $id, BackendInterface $backend, \DateTime $createdAt = null, \DateTime $updatedAt = null, $title = null)
    {
        parent::__construct($id, $backend, $createdAt, $updatedAt, $title);

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
