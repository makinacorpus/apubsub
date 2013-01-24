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
     * Disabled types. Keys are type names and values are any non null value
     *
     * @var array
     */
    protected $disabledTypes = array();

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
     * @param array $disabledTypes     List of disabled types
     */
    public function __construct(
        PubSubInterface $backend,
        $storeFormatted = false,
        $silentMode     = false,
        $disabledTypes  = null)
    {
        $this->backend        = $backend;
        $this->storeFormatted = $storeFormatted;
        $this->silentMode     = $silentMode;
        $this->typeRegistry   = new TypeRegistry();

        if (null !== $disabledTypes) {
            $this->disabledTypes = array_flip($disabledTypes);
        }
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
     *
     * @return \APubSub\SubscriberInterface
     */
    public function getSubscriberFor($id, $type = APB_TYPE_USER)
    {
        return $this
            ->backend
            ->getSubscriber($this->getChanId($type, $id));
    }

    /**
     * Subscribe an object to a chan
     *
     * @param scalar $targetId   Target object identifier
     * @param string $targetType Target object type
     * @param scalar $id         Subscriber object identifier
     * @param string $type       Subscriber object type
     */
    public function subscribe($targetId, $targetType, $id, $type = APB_TYPE_USER)
    {
        $subscriber = $this->getSubscriberFor($id, $type);
        $chanId     = $this->getChanId($targetType, $targetId);

        try {
            $subscriber->subscribe($chanId);
        } catch (ChannelDoesNotExistException $e) {
            $this->getBackend()->createChannel($chanId);
            $subscriber->subscribe($chanId);
        }
    }

    /**
     * Unsubscribe an object to a chan
     *
     * @param scalar $targetId   Target object identifier
     * @param string $targetType Target object type
     * @param scalar $id         Subscriber object identifier
     * @param string $type       Subscriber object type
     */
    public function unsubscribe($targetId, $targetType, $id, $type = APB_TYPE_USER)
    {
        $subscriber = $this
            ->getSubscriberFor($id, $type)
            ->unsubscribe(
                 $this->getChanId($targetType, $targetId));
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
        return $this->typeRegistry;
    }

    /**
     * Tell if given type is enabled
     *
     * @param string $type Type
     *
     * @return boolean     True if type is enabled otherwise false
     */
    public function isTypeEnabled($type)
    {
        return !isset($this->disabledTypes[$type]) && $this->typeRegistry->typeExists($type);
    }

    /**
     * Send notification
     *
     * If notification type is disabled the message will be dropped
     *
     * @param string $type Source object type
     * @param scalar $id   Source object identifier
     * @param mixed $data  Arbitrary data to send along
     */
    public function notify($type, $id, $data, $level = Notification::LEVEL_INFO)
    {
        if (!$this->isTypeEnabled($type)) {
            return;
        }

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
                ->send($contents, $type, $level);

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
