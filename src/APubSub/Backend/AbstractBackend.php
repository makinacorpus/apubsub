<?php

namespace APubSub\Backend;

use APubSub\BackendInterface;

/**
 * Common base implementation for most backends
 */
abstract class AbstractBackend extends AbstractObject implements
    BackendInterface
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::setOptions()
     */
    public function setOptions(array $options)
    {
        $this
            ->context
            ->setOptions($options);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::getSubscription()
     */
    public function getSubscription($id)
    {
        $subscriptionList = $this->getSubscriptions(array($id));

        return reset($subscriptionList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\BackendInterface::deleteSubscriptions()
     */
    public function deleteSubscriptions($idList)
    {
        // This doesn't sound revelant to optimize this method, subscriptions
        // should not be to transcient, they have not been meant to anyway:
        // deleting subscriptions will be a costy operation whatever the effort
        // to make in deleting them faster
        foreach ($idList as $id) {
            $this->deleteSubscription($id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageContainerInterface::flush()
     */
    public function flush()
    {
        $this->delete();
    }
}
