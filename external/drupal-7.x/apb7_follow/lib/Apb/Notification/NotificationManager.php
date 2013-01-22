<?php

namespace Apb\Notification;

use APubSub\Backend\DefaultMessage;
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
     * @var boolean
     */
    protected $storeFormatted = false;

    /**
     * @var boolean
     */
    protected $silentMode = false;

    /**
     * Default constructor
     *
     * @param PubSubInterface $backend Backend
     * @param boolean $storeFormatted  If set to true formatted messages content
     *                                 will be stored into messages
     * @param boolean $silentMode      If set to true this object will never
     *                                 predictible exceptions
     */
    public function __construct(
        PubSubInterface $backend,
        $storeFormatted = false,
        $silentMode = false)
    {
        $this->backend        = $backend;
        $this->storeFormatted = $storeFormatted;
        $this->silentMode     = $silentMode;
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
            $contents = array(
                'i' => $id,
                'd' => $data,
            );

            if ($this->storeFormatted) {
                // Quite a hack, but efficient
                $contents['f'] = $this
                    ->getTypeRegistry()
                    ->getInstance($type)
                    ->format(new Notification($this, $contents));
            }

            $this
                ->getBackend()
                ->getChannel($this->getChanId($type, $id))
                ->send($contents, $type);

        } catch (ChannelDoesNotExistException $e) {
            // Nothing to do, no channel means no subscription
        } catch (Exception $e) {
            // Any other exception must be shutdown when in production mode
            if (!$this->silentMode) {
                throw $e;
            }
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
