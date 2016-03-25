<?php

namespace MakinaCorpus\APubSub\Notification\EventDispatcher;

use MakinaCorpus\APubSub\Notification\NotificationService;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class AutoEventListener
{
    /**
     * @var NotificationService
     */
    private $service;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var string[][]
     */
    private $chanBlockingOverrides = [];

    /**
     * Default constructor
     *
     * @param NotificationService $service
     */
    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * Set channel overrides
     *
     * Set channel overrides where to send the notification, note that if you
     * use this, overrides will block sending notifications to the default
     * channel.
     *
     * @param string[][] $overrides
     *   First dimension keys are RESOURCETYPE:ACTION strings, corresponding to
     *   event names being raised, values are additional channels onto send the
     *   notifications
     */
    public function setChanBlockingOverrides(array $overrides)
    {
        $this->chanBlockingOverrides = $overrides;
    }

    /**
     * Get notification service
     *
     * @return NotificationService
     */
    final public function getNotificationService()
    {
        return $this->notificationService;
    }

    /**
     * Handle any event
     *
     * If this method does not fit to your need, you are encouraged to extend it
     *
     * @param Event $event
     */
    final public function __call($name, $args)
    {
        list($resourceType, $action) = explode('__', $name, 2);

        $this->onEvent($resourceType, $action, reset($args));
    }

    /**
     * Handle any event
     *
     * If this method does not fit to your need, you are advised to extend this
     * class and modify this method to suit your own needs
     *
     * @param string $resourceType
     * @param string $action
     * @param Event $event
     */
    protected function onEvent($resourceType, $action, Event $event)
    {
        $idList = null;
        $data   = null;
        $key    = $resourceType . ':' . $action;

        $sendOnDefaultChan = true;
        $additionalChanId = [];

        if ($event instanceof ResourceEvent) {
            $data = $event->getArguments();
            $idList = $event->getResourceIdList();

            $additionalChanId = $event->getChanIdList();
            $sendOnDefaultChan = $event->shouldKeepDefaultChan();

        } else if ($event instanceof GenericEvent) {
            $data = $event->getArguments();

            // Attempt to do a best guess at the resource identifier
            if (isset($event['id'])) {
                $idList = [$event['id']];
            } else {
                $subject = $event->getSubject();
                if (is_scalar($subject)) {
                    $idList = [$subject];
                } else if (method_exists($subject, 'getId')) {
                    $idList = [$subject->getId()];
                } else if (property_exists($subject, 'id')) {
                    $idList = [$subject->id];
                }
            }
        }

        if (isset($idList)) {

            // Proceed with pre-configured overrides
            if ($sendOnDefaultChan && isset($this->chanBlockingOverrides[$key])) {
                $additionalChanId = array_merge($additionalChanId, $this->chanBlockingOverrides[$key]);
                // FIXME override should not be blocking default chans
                // @TODO add a blocking boolean tag attribute (false by default)
                // $sendOnDefaultChan = false;
            }

            if ($additionalChanId) {
                foreach ($idList as $id) {
                    if ($sendOnDefaultChan) {
                        $additionalChanId[] = $this->service->getChanId($resourceType, $id);
                    }

                    $this->service->notifyChannel($additionalChanId, $resourceType, $id, $action, $data);
                }
            }
            else if ($sendOnDefaultChan) {
                foreach ($idList as $id) {
                    $this->service->notify($resourceType, $id, $action, $data);
                }
            }
            // We might end up sending nothing, as soon as any of the listeners
            // explicitly forbid to send on the default chan, and we have no
            // additional chans.
        }
    }
}
