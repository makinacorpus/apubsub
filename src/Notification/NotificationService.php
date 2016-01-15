<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\BackendInterface;
use MakinaCorpus\APubSub\Backend\DefaultMessage;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\MessageInterface;
use MakinaCorpus\APubSub\Notification\FormatterRegistry;
use MakinaCorpus\APubSub\SubscriberInterface;

/**
 * Notification service, single point of entry for the business layer
 */
class NotificationService
{
    /**
     * @var \MakinaCorpus\APubSub\BackendInterface
     */
    private $backend;

    /**
     * @var RegistryInterface
     */
    private $formatterRegistry;

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
     * @var string[]
     */
    private $currentSubscribers = array();

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

        if (null !== $disabledTypes) {
            $this->disabledTypes = array_flip($disabledTypes);
        }

        if (!$this->silentMode) {
            $this->formatterRegistry->setDebugMode();
        }
    }

    /**
     * Set formatter registry
     *
     * @param FormatterRegistry $formatterRegistry
     */
    public function setFormatterRegistry(FormatterRegistry $formatterRegistry)
    {
        $this->formatterRegistry = $formatterRegistry;
    }

    /**
     * Set a new subscriber as being the current one
     *
     * Setting a subscriber as being current will allow the notify() function
     * to automatically exclude him from the notifications recipients and avoid
     * a user to see his own messages
     *
     * @param string $name
     *   Subscriber name
     */
    public function addCurrentSubscriber($name)
    {
        if (!in_array($name, $this->currentSubscribers)) {
            $this->currentSubscribers[] = $name;
        }
    }

    /**
     * Reset current subscriber list
     */
    public function resetSubscribers()
    {
        $this->currentSubscribers = array();
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
     * @return SubscriberInterface
     *   Subscriber
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
     * @return SubscriberInterface
     *   Subscriber
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
     * Get subscriber names list
     *
     * @param string $type
     *   Susbcriber type identifier
     * @param scalar $idList
     *   Susbcriber identifiers list
     *
     * @return SubscriberInterface Subscriber
     */
    public function getSubscriberNameList($type, $idList)
    {
        if (!is_array($idList)) {
            $idList = array($idList);
        }

        array_walk($idList, function (&$id) use ($type) {
            $id = $type . ':' . $id;
        });

        return $idList;
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
     * @param string|array|\Traversable $name
     *   Subscriber name
     */
    public function unsubscribe($chanId, $name)
    {
        if (is_array($name) || $name instanceof \Traversable) {
            $this
                ->backend
                ->fetchSubscriptions(array(
                    Field::CHAN_ID => $chanId,
                    Field::SUBER_NAME => $name,
                ))
                ->delete()
            ;
        } else {
            $this
                ->backend
                ->getSubscriber($name)
                ->unsubscribe($chanId)
            ;
        }
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
            ->deleteSubscriber($name)
        ;
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
     * @return \MakinaCorpus\APubSub\Notification\FormatterRegistry Type registry
     */
    public function getFormatterRegistry()
    {
        return $this->formatterRegistry;
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
     * @param boolean $doExcludeCurrent
     *   If set to false the current subscribers won't be excluded from the
     *   current notification recipient list
     */
    public function notify($chanId, $type, array $data = null, $level = null, $doExcludeCurrent = true)
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
                    $this->getBackend(),
                    $contents,
                    $type);

                $contents['f'] = $this
                    ->getFormatterRegistry()
                    ->getInstance($type)
                    ->format(new Notification($this, $message));
            }

            if (!$doExcludeCurrent || empty($this->currentSubscribers)) {

                $message = $this
                    ->getBackend()
                    ->send($chanId, $contents, $type, null, $level)
                ;

            } else {

                $exclude = array();
                foreach ($this->currentSubscribers as $name) {
                    // Using getSubscriptionIds() will avoid an odd number of
                    // backend queries (at least for SQL backend). I do hope
                    // that our current subscriber does not have thousands...
                    $exclude = array_merge(
                        $exclude,
                        $this
                            ->getSubscriber($name)
                            ->getSubscriptionsIds()
                    );
                }

                $message = $this
                    ->getBackend()
                    ->send($chanId, $contents, $type, null, $level, $exclude)
                ;
            }

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
