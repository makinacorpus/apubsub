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
     * Get channel identifier from input parameters
     *
     * @param string $type Source object type
     * @param scalar $id   Source object identifier
     *
     * @return string          Channel identifier
     */
    public static function getChanId($type, $id)
    {
        return $type . ':' . $id;
    }

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
                ->getChannel(self::getChanId($type, $id))
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
