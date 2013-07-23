<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\CursorInterface;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\SubscriberInterface;

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
            CursorInterface::FIELD_SUBER_NAME => $id,
        ));

        $this->id = $id;

        if (null !== $subIdList) {
            $this->idList = $subIdList;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getId()
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptions()
     */
    public function getSubscriptions()
    {
        return $this
            ->context
            ->getBackend()
            ->getSubscriptions($this->idList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::hasSubscriptionFor()
     */
    final public function hasSubscriptionFor($chanId)
    {
        return isset($this->idList[$chanId]);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::getSubscriptionFor()
     */
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

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::subscribe()
     */
    public function subscribe($chanId)
    {
        $subscription = $this
            ->context
            ->getBackend()
            ->subscribe($chanId, $this->id);

        $this->idList[$chanId] = $subscription->getId();

        return $subscription;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::unsubscribe()
     */
    public function unsubscribe($chanId)
    {
        try {
            if (isset($this->idList[$chanId])) {
                // See the getSubscriptionFor() implementation
                $this
                    ->context
                    ->getBackend()
                    ->getSubscription($this->idList[$chanId])
                    ->delete();
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // An exception here means a subscription for this channel does
            // not exist and that we can pass safely 
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::delete()
     */
    public function delete()
    {
        return $this
            ->context
            ->getBackend()
            ->deleteSubscriptions($this->idList);
    }
}
