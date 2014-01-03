<?php

namespace APubSub\Notification;

use APubSub\BackendInterface;
use APubSub\Backend\DefaultMessage;
use APubSub\Error\ChannelDoesNotExistException;
use APubSub\MessageInterface;
use APubSub\Notification\Registry\ChanTypeRegistry;
use APubSub\Notification\Registry\FormatterRegistry;

/**
 * Notification service, single point of entry for the business layer
 */
class NotificationService
{
    /**
     * @var \APubSub\BackendInterface
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
     * @param BackendInterface $backend Backend
     * @param boolean $storeFormatted   If set to true formatted messages content
     *                                  will be stored into messages
     * @param boolean $silentMode       If set to true this object will never
     *                                  predictible exceptions
     * @param array $disabledTypes      List of disabled types
     */
    public function __construct(
        BackendInterface $backend,
        $storeFormatted = false,
        $silentMode     = false,
        $disabledTypes  = null)
    {
        $this->backend           = $backend;
        $this->storeFormatted    = $storeFormatted;
        $this->silentMode        = $silentMode;
        $this->formatterRegistry = new FormatterRegistry();
        $this->chanTypeRegistry  = new ChanTypeRegistry();

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
     * @return string      Channel identifier
     */
    public function getChanId($type, $id)
    {
        return $type . ':' . $id;
    }

    /**
     * Get subscriber
     *
     * @param string $name
     *   Subscriber name
     *
     * @return SubscriberInterface Subscriber
     */
    public function getSubscriber($name)
    {
        return $this->backend->getSubscriber($name);
    }

    /**
     * Get subscriber
     *
     * @param string $type
     *   Susbcriber type identifier
     * @param scalar $id
     *   Susbcriber identifier
     *
     * @return SubscriberInterface Subscriber
     */
    public function getSubscriberFor($type, $id)
    {
        return $this->backend->getSubscriber($this->getSubscriberName($type, $id));
    }

    /**
     * Get subscriber
     *
     * @param string $type
     *   Susbcriber type identifier
     * @param scalar $id
     *   Susbcriber identifier
     *
     * @return SubscriberInterface Subscriber
     */
    public function getSubscriberName($type, $id)
    {
        return $type . ':' . $id;
    }

    /**
     * Subscribe to a chan
     *
     * This method will implicetely create the channel if non existant
     *
     * @param string $chanId
     *   Channel identifier list or single value
     * @param string $name
     *   Subscriber name
     */
    public function subscribe($chanId, $name)
    {
        $subscriber = $this->getSubscriber($name);

        try {
            $subscriber->subscribe($chanId);
        } catch (ChannelDoesNotExistException $e) {
            $this->getBackend()->createChannel($chanId);
            $subscriber->subscribe($chanId);
        }
    }

    /**
     * Unsubscribe to a chan
     *
     * @param string|array $chanId
     *   Channel identifier list or single value
     * @param string $name
     *   Subscriber name
     */
    public function unsubscribe($chanId, $name)
    {
        $subscriber = $this
            ->getSubscriber($name)
            ->unsubscribe($chanId);
    }

    /**
     * Delete all subscriber information
     *
     * @param string $name
     *   Subscriber name
     */
    public function deleteSubscriber($name)
    {
        $this
            ->getBackend()
            ->deleteSubscriber($name);
    }

    /**
     * Get backend
     *
     * @return BackendInterface Backend
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
     * @param int|array $chanId
     *   Channel identifier list or single value
     * @param string $type
     *   Notification type
     * @param array $data
     *   Arbitrary data to send along
     * @param int $level
     *   Arbitrary level, see Notification::LEVEL_* constants. This value is
     *   purely arbitrary, it is up to the business layer to do something with
     *   it. It does not alters the notification system behavior.
     */
    public function notify($chanId, $type, array $data = null, $level = null)
    {
        if (!$this->isTypeEnabled($type)) {
            return;
        }
        if (null === $level) {
            $level = Notification::LEVEL_INFO;
        }

        try {
            $contents = array('d' => $data);

            if ($this->storeFormatted) {
                // Quite a hack, but efficient, we need a false message to
                // exist in order to create a false notification, so we can
                // force it to be rendered before the message exists
                $message = new DefaultMessage(
                    $this
                        ->getBackend()
                        ->getContext(),
                    $contents,
                    $type);

                $contents['f'] = $this
                    ->getFormatterRegistry()
                    ->getInstance($type)
                    ->format(new Notification($this, $message));
            }

            $message = $this
                ->getBackend()
                ->send($chanId, $contents, $type, $level);

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
