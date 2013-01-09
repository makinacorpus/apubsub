<?php

namespace Apb\Notification;

use APubSub\Error\ChannelDoesNotExistException;
use APubSub\MessageInterface;
use APubSub\PubSubInterface;

/**
 * Single entry point for notification handling
 */
class NotificationManager
{
    /**
     * @var \APubSub\PubSubInterface
     */
    protected $backend;

    /**
     * @var \Apb\Notification\TypeRegistry
     */
    protected $typeRegistry;

    /**
     * Default constructor
     *
     * @param PubSubInterface $backend Backend
     */
    public function __construct(PubSubInterface $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Get channel identifier from input parameters
     *
     * @param string $type Source object type
     * @param scalar $id   Source object identifier
     *
     * @return string          Channel identifier
     */
    public function getChanId($type, $id)
    {
        return $type . ':' . $id;
    }

    /**
     * Get subscriber for object
     *
     * @param int $id      Object identifier
     * @param string $type Type identifier, if none given assume
     *                     user account
     */
    public function getSubscriberFor($id, $type = APB_TYPE_USER)
    {
        return $this
            ->backend
            ->getSubscriber($this->getChanId($type, $id));
    }

    /**
     * Get backend
     *
     * @return \APubSub\PubSubInterface Backend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Get type registry
     *
     * @return \Apb\Notification\TypeRegistry Type registry
     */
    public function getTypeRegistry()
    {
        if (null === $this->typeRegistry) {
            $this->typeRegistry = new TypeRegistry();
        }

        return $this->typeRegistry;
    }

    /**
     * Send notification
     *
     * @param string $type Source object type
     * @param scalar $id   Source object identifier
     * @param mixed $data  Arbitrary data to send along
     */
    public function notify($type, $id, $data)
    {
        try {
            $this
                ->getBackend()
                ->getChannel($this->getChanId($type, $id))
                ->send(array('i' => $id, 'd' => $data), $type);
        } catch (ChannelDoesNotExistException $e) {
          // Nothing to do, no channel means no subscription
        }
    }

    /**
     * Get notification instance from message
     *
     * @param MessageInterface $message       Message
     *
     * @return \Apb\Notification\Notification Notification
     */
    public function getNotification(MessageInterface $message)
    {
        return new Notification($this, $message);
    }
}
