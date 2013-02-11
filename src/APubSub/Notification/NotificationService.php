<?php

namespace APubSub\Notification;

use APubSub\Backend\VolatileMessage;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\MessageInterface;
use APubSub\Notification\Registry\ChanTypeRegistry;
use APubSub\Notification\Registry\QueueRegistry;
use APubSub\Notification\Registry\FormatterRegistry;
use APubSub\PubSubInterface;

/**
 * Notification service, single point of entry for the business layer
 */
class NotificationService
{
    /**
     * User subscriber prefix
     */
    const SUBSCRIBER_USER = 'u';

    /**
     * @var \APubSub\PubSubInterface
     */
    private $backend;

    /**
     * @var RegistryInterface
     */
    private $formatterRegistry;

    /**
     * @var RegistryInterface
     */
    private $chanTypeRegistry;

    /**
     * @var RegistryInterface
     */
    private $queueRegistry;

    /**
     * Disabled types. Keys are type names and values are any non null value
     *
     * @var array
     */
    private $disabledTypes = array();

    /**
     * @var boolean
     */
    private $storeFormatted = false;

    /**
     * @var boolean
     */
    private $silentMode = false;

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
        $this->backend           = $backend;
        $this->storeFormatted    = $storeFormatted;
        $this->silentMode        = $silentMode;
        $this->formatterRegistry = new FormatterRegistry();
        $this->chanTypeRegistry  = new ChanTypeRegistry();
        $this->queueRegistry     = new QueueRegistry();

        if (null !== $disabledTypes) {
            $this->disabledTypes = array_flip($disabledTypes);
        }

        if (!$this->silentMode) {
            $this->formatterRegistry->setDebugMode();
            $this->chanTypeRegistry->setDebugMode();
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
     * Get subscriber
     *
     * @param scalar $id           Susbcriber identifier
     * @param string $type         Susbcriber type identifier
     *
     * @return SubscriberInterface Subscriber
     */
    public function getSubscriber($id, $type = self::SUBSCRIBER_USER)
    {
        return $this
            ->backend
            ->getSubscriber($type . ':' . $id);
    }

    /**
     * Get queue subscriber
     *
     * @param string $queueType Queue type
     * @param scalar $id        Susbcriber identifier
     * @param string $type      Subscriber type
     */
    public function getQueueSubscriber($queueType, $id, $type = self::SUBSCRIBER_USER)
    {
        return $this
            ->backend
            ->getSubscriber($type . ':' . $queueType . ':' . $id);
    }

    /**
     * Subscribe an object to a chan
     *
     * @param string $chanId Channel identifier
     * @param scalar $id     Subscriber object identifier
     * @param string $type   Subscriber object type
     */
    public function subscribe($chanId, $id, $type = self::SUBSCRIBER_USER)
    {
        $subscriber = $this->getSubscriber($id, $type);

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
     * @param string $chanId Channel identifier
     * @param scalar $id     Subscriber object identifier
     * @param string $type   Subscriber object type
     */
    public function unsubscribe($chanId, $id, $type = self::SUBSCRIBER_USER)
    {
        $subscriber = $this
            ->getSubscriber($id, $type)
            ->unsubscribe($chanId);
    }

    /**
     * Delete all subscriber information
     *
     * @param scalar $id        Susbcriber identifier
     * @param string $type      Subscriber type
     */
    public function deleteSubscriber($id, $type = self::SUBSCRIBER_USER)
    {
        $this->getSubscriber($id, $type)->delete();

        foreach ($this->queueRegistry->getAllInstances() as $instance) {
            $this->getQueueSubscriber($instance->getType(), $id, $type)->delete();
        }
    }

    /**
     * Get backend
     *
     * @return PubSubInterface Backend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Get type registry
     *
     * @return \APubSub\Notification\Registry\FormatterRegistry Type registry
     */
    public function getFormatterRegistry()
    {
        return $this->formatterRegistry;
    }

    /**
     * Get channel type registry
     *
     * @return \APubSub\Notification\Registry\ChanTypeRegistry Type registry
     */
    public function getChanTypeRegistry()
    {
        return $this->chanTypeRegistry;
    }

    /**
     * Get communiation queue registry
     *
     * @return \APubSub\Notification\Registry\QueueRegistry Type registry
     */
    public function getQueueRegistry()
    {
        return $this->queueRegistry;
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
        return !isset($this->disabledTypes[$type]) && $this->formatterRegistry->typeExists($type);
    }

    /**
     * Send notification
     *
     * If notification type is disabled the message will be dropped
     *
     * @param string $type Source object type
     * @param scalar $id   Source object identifier
     * @param mixed $data  Arbitrary data to send along
     * @param int $level   Arbitrary level, see Notification::LEVEL_* constants.
     *                     This value is purely arbitrary, it is up to the
     *                     business layer to do something with it. It does not
     *                     alters the notification system behavior
     * @param int $chanId  Forces a channel identifier, for convenience if this
     *                     is left to null the channel identifier will be
     *                     computed from the given $type and $id parameters
     *                     using the getChanId() method
     */
    public function notify($type, $id, $data, $level = null, $chanId = null)
    {
        if (!$this->isTypeEnabled($type)) {
            return;
        }
        if (null === $level) {
            $level = Notification::LEVEL_INFO;
        }
        if (null === $chanId) {
            $chanId = $this->getChanId($type, $id);
        }

        try {
            $contents = array(
                'i' => $id,
                'd' => $data,
            );

            if ($this->storeFormatted) {
                // Quite a hack, but efficient, we need a false message to
                // exist in order to create a false notification, so we can
                // force it to be rendered before the message exists
                $message = new VolatileMessage($contents, $type);

                $contents['f'] = $this
                    ->getFormatterRegistry()
                    ->getInstance($type)
                    ->format(new Notification($this, $message));
            }

            $message = $this
                ->getBackend()
                ->getChannel($chanId)
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
     * @param MessageInterface $message Message
     *
     * @return Notification             Notification
     */
    public function getNotification(MessageInterface $message)
    {
        return new Notification($this, $message);
    }
}
