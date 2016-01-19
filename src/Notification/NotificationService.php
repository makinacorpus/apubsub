<?php

namespace MakinaCorpus\APubSub\Notification;

use MakinaCorpus\APubSub\BackendInterface;
use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\Misc;
use MakinaCorpus\APubSub\SubscriberInterface;
use MakinaCorpus\APubSub\MessageInstanceInterface;

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
     * @param string $name
     *   Subscriber name
     */
    public function addCurrentSubscriber($id, $type = null)
    {
        $name = $this->getSubscriberName($id, $type);

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
     * @param string $type
     *   Source object type
     * @param scalar $id
     *   Source object identifier
     *
     * @return string
     *   Channel identifier
     */
    protected function getChanId($type, $id)
    {
        return $type . ':' . $id;
    }

    /**
     * Get channel identifier from input parameters
     *
     * @param string $type
     *   Source object type
     * @param string[] $idList
     *   Source object identifier
     *
     * @return string
     *   Channel identifier
     */
    protected function getChanIdList($type, $idList)
    {
        $ret = [];

        foreach (Misc::toIterable($idList) as $index => $id) {
            $ret[$index] = $this->getChanId($type, $id);
        }

        return $ret;
    }

    /**
     * Get subscriber name
     *
     * @param scalar $id
     *   Susbcriber identifier
     * @param string $type
     *   Susbcriber type identifier
     *
     * @return string
     *   Subscriber name
     */
    protected function getSubscriberName($id, $type = null)
    {
        if (!$type) {
            $type  = self::SUBER_TYPE_DEFAULT;
        }

        return $type . ':' . $id;
    }

    /**
     * Get subscriber names list
     *
     * @param string[] $idList
     *   Susbcriber identifiers list
     * @param string $type
     *   Susbcriber type identifier
     *
     * @return SubscriberInterface Subscriber
     */
    protected function getSubscriberNameList($idList, $type)
    {
        $ret = [];

        foreach (Misc::toIterable($idList) as $index => $id) {
            $ret[$index] = $this->getSubscriberName($id, $type);
        }

        return $ret;
    }

    /**
     * Get subscriber
     *
     * @param string $id
     *   Subscriber identifier
     * @param string $type
     *   Subscriber type
     *
     * @return SubscriberInterface
     *   Subscriber
     */
    public function getSubscriber($id, $type = null)
    {
        return $this->backend->getSubscriber($this->getSubscriberName($id, $type));
    }

    /**
     * Subscribe to a chan
     *
     * This method will implicetely create the channel if non existant
     *
     * @param string $objectType
     * @param int|string|int[]|string[] $objectId
     * @param int|string|int[]|string[] $suberId
     * @param string $suberType
     */
    public function subscribe($objectType, $objectId, $suberId, $suberType = null)
    {
        $chanIdList = $this->getChanIdList($objectType, $objectId);

        // Create the channels if they don't exists, no errors
        $this->backend->createChannels($chanIdList, true);

        // FIXME: Optimize this whenever this becomes possible
        foreach ($this->getSubscriberNameList($suberId, $suberType) as $name) {
            foreach ($chanIdList as $chanId) {
                $this->backend->subscribe($chanId, $name);
            }
        }
    }

    /**
     * Unsubscribe to a chan
     *
     * @param string $objectType
     * @param int|string|int[]|string[] $objectId
     * @param int|string|int[]|string[] $suberId
     * @param string $suberType
     */
    public function unsubscribe($objectType, $objectId, $suberId, $suberType = null)
    {
        $this
            ->backend
            ->fetchSubscriptions([
                Field::CHAN_ID    => $this->getChanIdList($objectType, $objectId),
                Field::SUBER_NAME => $this->getSubscriberNameList($suberId, $suberType),
            ])
            ->delete()
        ;
    }

    /**
     * Delete all subscriber information
     *
     * @param int|string|int[]|string[] $id
     * @param string $type
     */
    public function deleteSubscriber($id, $type = null)
    {
        $this->backend->deleteSubscriber($this->getSubscriberName($id, $type));
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
     * Send notification
     *
     * If notification type is disabled the message will be dropped
     *
     * @param string $type
     *   Resource type
     * @param string|string[] $id
     *   List of resource identifiers impacted on which the action has been done
     * @param string $action
     *   Resource action
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
    public function notify($type, $id, $action, $data = [], $level = null, $doExcludeCurrent = true)
    {
        if (null === $level) {
            $level = NotificationInterface::LEVEL_INFO;
        }

        $chanIdList = $this->getChanIdList($type, $id);

        $data = ['data' => $data];
        $data['id'] = Misc::toArray($id);
        $type .= ':' . $action;

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

    /**
     * Get subscriber notifications
     *
     * @param string $id
     *   Subscriber identifier
     * @param string|string[] $type
     *   Subscriber type
     *
     * @return CursorInterface|NotificationInterface[]
     */
    public function fetchForSubscriber($id, $type = null, array $conditions = [])
    {
        $conditions[Field::SUBER_NAME] = $this->getSubscriberNameList($id, $type);

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
     * @param string $type
     *   Subscriber type
     * @param string|string[] $id
     *   Subscriber identifier
     *
     * @return CursorInterface|ThreadInterface[]
     */
    public function fetchForResource($id, $type = null, array $conditions = [])
    {
        $conditions[Field::CHAN_ID] = $this->getChanIdList($type, $id);

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
