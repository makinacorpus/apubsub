<?php

namespace MakinaCorpus\APubSub\Notification\EventDispatcher;

use MakinaCorpus\APubSub\Misc;

use Symfony\Component\EventDispatcher\GenericEvent;

class ResourceEvent extends GenericEvent
{
    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var scalar[]
     */
    protected $resourceIdList;

    /**
     * @var scalar[]
     */
    protected $userIdList;

    /**
     * @var string[]
     */
    protected $chanIdList = [];

    /**
     * @var boolean
     */
    protected $keepDefaultChan = true;

    /**
     * Default constructor
     *
     * @param string $resourceType
     * @param scalar|scalar[] $resourceId
     * @param mixed $resource
     * @param scalar|scalar[] $userId
     * @param array $data
     */
    public function __construct($resourceType, $resourceIdList, $userId = null, array $data = [])
    {
        parent::__construct(null, $data + ['uid' => $userId]);

        $this->resourceType = $resourceType;
        $this->resourceIdList = Misc::toArray($resourceIdList);
    }

    /**
     * Add channel to notification destination
     *
     * @param string $resourceType
     * @param scalar|scalar[] $resourceId
     */
    public function addResourceChanId($resourceType, $resourceId)
    {
        foreach (Misc::toIterable($resourceId) as $id) {
            $this->chanIdList[] = $resourceType . ':' . $id;
        }
    }

    /**
     * Add channel to notification destination
     *
     * @param string|string[] $chanId
     */
    public function addArbitraryChanId($chanId)
    {
        foreach (Misc::toIterable($chanId) as $id) {
            $this->chanIdList[] = $id;
        }
    }

    /**
     * Mark the event for not sending the notification on the default chan
     */
    public function ignoreDefaultChan()
    {
        $this->keepDefaultChan = false;
    }

    /**
     * Mark the event for sending the notification on the default chan
     */
    public function keepDefaultChan()
    {
        $this->keepDefaultChan = true;
    }

    /**
     * Should this event notify upon the default channel
     */
    public function shouldKeepDefaultChan()
    {
        return $this->keepDefaultChan;
    }

    /**
     * Get resource type
     *
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Get resources identifiers
     *
     * @return string[]
     */
    public function getResourceIdList()
    {
        return $this->resourceIdList;
    }

    /**
     * Get additional channels
     *
     * @return string[]
     */
    public function getChanIdList()
    {
        return $this->chanIdList;
    }
}
