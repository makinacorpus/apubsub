<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\BackendInterface;
use MakinaCorpus\APubSub\Backend\DefaultMessage;
use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\MessageInstanceInterface;
use MakinaCorpus\APubSub\Misc;
use MakinaCorpus\APubSub\SubscriberInterface;
use MakinaCorpus\APubSub\Backend\DefaultMessageInstance;

/**
 * Notification service, single point of entry for the business layer
 */
class NotificationService
{
    /**
     * Default suber type
     */
    const SUBER_TYPE_DEFAULT = '_u';

    /**
     * @var BackendInterface
     */
    private $backend;

    /**
     * @var FormatterRegistryInterface
     */
    private $formatterRegistry;

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
    private $currentSubscribers = [];

    /**
     * Default constructor
     *
     * @param BackendInterface $backend
     * @param boolean $silentMode
     *   If set to true this object will never predictible exceptions
     */
    public function __construct(BackendInterface $backend, $silentMode = false)
    {
        $this->backend           = $backend;
        $this->silentMode        = $silentMode;
        $this->formatterRegistry = new DefaultFormatterRegistry();

        if (!$this->silentMode) {
            $this->formatterRegistry->setDebugMode();
        }
    }

    /**
     * Set formatter registry
     *
     * @param FormatterRegistryInterface $formatterRegistry
     */
    public function setFormatterRegistry(FormatterRegistryInterface $formatterRegistry)
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
     * @param string $subscriberId
     * @param string $subscriberType
     */
    public function addCurrentSubscriber($subscriberId, $subscriberType = null)
    {
        $name = $this->getSubscriberName($subscriberId, $subscriberType);

        if (!in_array($name, $this->currentSubscribers)) {
            $this->currentSubscribers[] = $name;
        }
    }

    /**
     * Reset current subscriber list
     */
    public function resetCurrentSubscribers()
    {
        $this->currentSubscribers = [];
    }

    /**
     * Get channel identifier from input parameters
     *
     * @param string $resourceType
     * @param string $resourceId
     *
     * @return string
     */
    public function getChanId($resourceType, $resourceId)
    {
        return $resourceType . ':' . $resourceId;
    }

    /**
     * Get channel identifier from input parameters
     *
     * @param string $resourceType
     * @param string[] $resourceIdList
     *
     * @return string[]
     */
    public function getChanIdList($resourceType, $resourceIdList)
    {
        $ret = [];

        foreach (Misc::toIterable($resourceIdList) as $index => $resourceId) {
            $ret[$index] = $this->getChanId($resourceType, $resourceId);
        }

        return $ret;
    }

    /**
     * Get subscriber name
     *
     * @param string $subscriberId
     * @param string $subscriberType
     *
     * @return string
     */
    public function getSubscriberName($subscriberId, $subscriberType = null)
    {
        if (!$subscriberType) {
            $subscriberType  = self::SUBER_TYPE_DEFAULT;
        }

        return $subscriberType . ':' . $subscriberId;
    }

    /**
     * Get subscriber names list
     *
     * @param string[] $subscriberIdList
     * @param string $type
     *
     * @return string[]
     */
    protected function getSubscriberNameList($subscriberIdList, $type)
    {
        $ret = [];

        foreach (Misc::toIterable($subscriberIdList) as $index => $subscriberId) {
            $ret[$index] = $this->getSubscriberName($subscriberId, $type);
        }

        return $ret;
    }

    /**
     * Get subscriber
     *
     * @param string $subscriberId
     * @param string $subscriberType
     *
     * @return SubscriberInterface
     */
    public function getSubscriber($subscriberId, $subscriberType = null)
    {
        return $this->backend->getSubscriber($this->getSubscriberName($subscriberId, $subscriberType));
    }

    /**
     * Remove all data from the given resource
     *
     * @param string $resourceType
     * @param string|string[] $resourceId
     */
    public function deleteResource($resourceType, $resourceId)
    {
        $this->backend->deleteChannels($this->getChanIdList($resourceType, $resourceId));
    }

    /**
     * Subscribe to a chan
     *
     * This method will implicetely create the channel if non existant
     *
     * @param string $resourceType
     * @param string|string[] $resourceId
     * @param string|string[] $subscriberId
     * @param string $subscriberType
     */
    public function subscribe(
        $resourceType,
        $resourceId,
        $subscriberId,
        $subscriberType = null
    ) {
        $chanIdList = $this->getChanIdList($resourceType, $resourceId);

        // Create the channels if they don't exists, no errors
        $this->backend->createChannels($chanIdList, true);

        // FIXME: Optimize this whenever this becomes possible
        foreach ($this->getSubscriberNameList($subscriberId, $subscriberType) as $name) {
            foreach ($chanIdList as $chanId) {
                $this->backend->subscribe($chanId, $name);
            }
        }
    }

    /**
     * Unsubscribe to a chan
     *
     * @param string $resourceType
     * @param string|string[] $resourceId
     * @param string|string[] $subscriberId
     * @param string $subscriberType
     */
    public function unsubscribe(
        $resourceType,
        $resourceId,
        $subscriberId,
        $subscriberType = null
    ) {
        $this
            ->backend
            ->fetchSubscriptions([
                Field::CHAN_ID    => $this->getChanIdList($resourceType, $resourceId),
                Field::SUBER_NAME => $this->getSubscriberNameList($subscriberId, $subscriberType),
            ])
            ->delete()
        ;
    }

    /**
     * Delete all subscriber information
     *
     * @param string[] $subscriberId
     * @param string $subscriberType
     */
    public function deleteSubscriber($subscriberId, $subscriberType = null)
    {
        $this->backend->deleteSubscriber($this->getSubscriberName($subscriberId, $subscriberType));
    }

    /**
     * Delete all subscriber information
     *
     * @param string[] $subscriberIdList
     * @param string $subscriberType
     */
    public function deleteSubscriberList($subscriberIdList, $subscriberType = null)
    {
        $this->backend->deleteSubscribers($this->getSubscriberNameList($subscriberIdList, $subscriberType));
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
     * @return FormatterRegistryInterface
     */
    public function getFormatterRegistry()
    {
        return $this->formatterRegistry;
    }

    /**
     * Notify an action happened on a resource
     *
     * @param string $resourceType
     * @param string|string[] $resourceId
     * @param string $action
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
    public function notify(
        $resourceType,
        $resourceId,
        $action,
        $data             = [],
        $level            = null,
        $doExcludeCurrent = true
    ) {
        return $this
            ->notifyChannel(
                $this->getChanIdList($resourceType, $resourceId),
                $resourceType,
                $resourceId,
                $action,
                $data,
                $level,
                $doExcludeCurrent
            )
        ;
    }

    /**
     * Notify an action happened on a resource in an arbitrary channel
     *
     * @param string|string[] $chanId
     * @param string $resourceType
     * @param string|string[] $resourceId
     * @param string $action
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
    public function notifyChannel(
        $chanId,
        $resourceType,
        $resourceId,
        $action,
        $data             = [],
        $level            = null,
        $doExcludeCurrent = true
    ) {
        if (null === $level) {
            $level = NotificationInterface::LEVEL_INFO;
        }

        $chanIdList = Misc::toIterable($chanId);

        $data = ['data' => $data];
        $data['id'] = Misc::toArray($resourceId);
        $type = $resourceType . ':' . $action;

        try {
            $exclude = [];

            if ($doExcludeCurrent && $this->currentSubscribers) {
                foreach ($this->currentSubscribers as $name) {
                    // Using getSubscriptionIds() will avoid an odd number of
                    // backend queries (at least for SQL backend). I do hope
                    // that our current subscriber does not have thousands...
                    $exclude = array_merge(
                        $exclude,
                        $this->backend->getSubscriber($name)->getSubscriptionsIds()
                    );
                }
            }

            $this->prepareNotification($type, $data, $level);

            // Channels are not automatically created
            // TODO Find a better a way, to filter out non-existing channels
            $this->backend->createChannels($chanIdList, true);

            $this->backend->send($chanIdList, $data, $type, null, $level, $exclude);

        } catch (ChannelDoesNotExistException $e) {
            // Nothing to do, no channel means no subscription
        } catch (Exception $e) {
            // Any other exception must be shutdown when in production mode
            if (!$this->silentMode) {
                throw $e;
            }
        }
    }

    private function prepareNotification($type, &$data = [], $level = null)
    {
        $formatter = $this->formatterRegistry->get($type);

        if ($formatter instanceof CacheableFormatterInterface) {

            // Build a fake message, in order to be able to get a notification
            // object from it which allows us to pre-render the cached version
            // if appliable and store it along the message contents
            $notification = $this->getNotification(new DefaultMessageInstance($this->backend, null, $data, null, null, new \DateTime(), $type));

            $additions = $formatter->prepareCache($notification);
            if ($additions) {
                $data['data'] = array_merge($data, $additions);
            }
        }
    }

    /**
     * Get subscriber notifications
     *
     * @param string|string[] $subscriberId
     * @param string $subscriberType
     * @param string[] $conditions
     *
     * @return CursorInterface|NotificationInterface[]
     */
    public function fetchForSubscriber($subscriberId, $subscriberType = null, array $conditions = [])
    {
        $conditions[Field::SUBER_NAME] = $this->getSubscriberNameList($subscriberId, $subscriberType);

        return new NotificationCursor(
            $this,
            $this
                ->backend
                ->fetch($conditions)
                ->addSort(Field::MSG_SENT, CursorInterface::SORT_DESC)
                ->addSort(Field::MSG_ID, CursorInterface::SORT_DESC)
            )
        ;
    }

    /**
     * Get subscriber notifications
     *
     * @param string $resourceType
     * @param string|string[] $resourceId
     * @param string[] $conditions
     *
     * @return CursorInterface|ThreadInterface[]
     */
    public function fetchForResource($resourceType, $resourceId, array $conditions = [])
    {
        $conditions[Field::CHAN_ID] = $this->getChanIdList($resourceType, $resourceId);

        return new NotificationCursor(
            $this,
            $this
                ->backend
                ->fetch($conditions)
                ->addSort(Field::MSG_SENT, CursorInterface::SORT_DESC)
                ->addSort(Field::MSG_ID, CursorInterface::SORT_DESC)
            )
        ;
    }

    /**
     * Build notification from message instance
     *
     * @param MessageInstanceInterface $message
     *
     * @return NotificationInterface
     */
    public function getNotification(MessageInstanceInterface $message)
    {
        return new DefaultNotification($message, $this->formatterRegistry->get($message->getType()));
    }
}

