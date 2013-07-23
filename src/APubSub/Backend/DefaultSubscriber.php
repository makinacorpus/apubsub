<?php

namespace APubSub\Backend;

use APubSub\ContextInterface;
use APubSub\CursorInterface;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\SubscriberInterface;

abstract class DefaultSubscriber extends AbstractMessageContainer implements
    SubscriberInterface
{
    /**
     * @var scalar
     */
    private $id;

    /**
     * @var array
     */
    // @todo Switch back to private once no other implementations exists
    protected $idList = null;

    /**
     * Default constructor
     *
     * @param int $id                   Identifier
     * @param ContextInterface $context Context
     */
    public function __construct($id, ContextInterface $context)
    {
        parent::__construct($context, array(
            CursorInterface::FIELD_SUBER_NAME => $id,
        ));

        $this->id = $id;
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
     * @see \APubSub\SubscriberInterface::unsubscribe()
     */
    public function unsubscribe($chanId)
    {
        try {
            if (isset($this->idList[$chanId])) {
                // See the getSubscriptionFor() implementation
                $this->context
                    ->getBackend()
                    ->getSubscription($this->idList[$chanId])
                    ->delete();
            }
        } catch (SubscriptionDoesNotExistException $e) {
            // All OK
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\SubscriberInterface::delete()
     */
    public function delete()
    {
        $this
            ->context
            ->getBackend()
            ->deleteSubscriptions($this->idList);
    }
}
