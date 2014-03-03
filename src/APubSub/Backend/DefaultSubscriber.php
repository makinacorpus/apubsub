<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\SubscriberInterface;
use APubSub\Field;

/**
 * Default subscriber implementation that will fit most backends
 */
class DefaultSubscriber extends AbstractMessageContainer implements
    SubscriberInterface
{
    /**
     * @var scalar
     */
    private $id;

    /**
     * @var array
     */
    private $idList = null;

    /**
     * Default constructor
     *
     * @param int $id                   Identifier
     * @param ContextInterface $context Context
     * @param array $subIdList          Subscription identifiers list where
     *                                  keys are channel identifiers and values
     *                                  are subscriptions identifiers
     */
    public function __construct($id, ContextInterface $context, array $subIdList = null)
    {
        parent::__construct($context, array(
            Field::SUBER_NAME => $id,
        ));

        $this->id = $id;

        if (null !== $subIdList) {
            $this->idList = $subIdList;
        }
    }

    final public function getId()
    {
        return $this->id;
    }

    public function getSubscriptions()
    {
        return $this
            ->context
            ->getBackend()
            ->getSubscriptions(array_values($this->idList));
    }

    public function getSubscriptionsIds()
    {
        if (null === $this->idList) {
            return array();
        }

        return array_values($this->idList);
    }

    final public function hasSubscriptionFor($chanId)
    {
        return isset($this->idList[$chanId]);
    }

    public function getSubscriptionFor($chanId)
    {
        if (!isset($this->idList[$chanId])) {
            throw new SubscriptionDoesNotExistException();
        }

        // If another piece of code effectively deleted the subscription, but
        // we still work on an outdated cache, this should throw the same
        // exception as upper. Don't think this cannot happen, this *will*
        // happen, nothing prevent you from deleting a subscription using the
        // backend after you loaded this subscriber instance
        return $this
            ->context
            ->getBackend()
            ->getSubscription($this->idList[$chanId]);
    }

    public function subscribe($chanId)
    {
        $subscription = $this
            ->context
            ->getBackend()
            ->subscribe($chanId, $this->id);

        $this->idList[$chanId] = $subscription->getId();

        return $subscription;
    }

    public function unsubscribe($chanId)
    {
        try {
            if (isset($this->idList[$chanId])) {
                // See the getSubscriptionFor() implementation
                $this
                    ->context
                    ->getBackend()
                    ->fetchSubscriptions(array(
                        Field::SUB_ID => $this->idList[$chanId], 
                    ))
                    ->delete();

                unset($this->idList[$chanId]);
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // An exception here means a subscription for this channel does
            // not exist and that we can pass safely 
        }
    }
}
