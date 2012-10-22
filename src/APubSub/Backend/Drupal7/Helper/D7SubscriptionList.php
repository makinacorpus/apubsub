<?php

namespace APubSub\Backend\Drupal7\Helper;

use APubSub\Helper\ListInterface;

/**
 * Drupal 7 implementation of subscription list helper
 */
class D7SubscriptionList extends AbstractD7List
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            ListInterface::SORT_FIELD_ID,
            ListInterface::SORT_FIELD_CREATED,
            ListInterface::SORT_FIELD_SUB_STATUS,
        );
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::createdQuery()
     */
    protected function createdQuery()
    {
        return $this
            ->context
            ->dbConnection
            ->select('apb_sub', 's')
            ->fields('s', array('id'));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObject()
     */
    protected function loadObject($id)
    {
        return $this->context->backend->getSubscription($id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObjects()
     */
    protected function loadObjects($idList)
    {
        return $this->context->backend->getSubscriptions($idList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::getSortColumn()
     */
    protected function getSortColumn($sort)
    {
        switch ($sort)
        {
            case ListInterface::SORT_FIELD_ID:
                return 's.id';

            case ListInterface::SORT_FIELD_CREATED:
                return 's.created';

            case ListInterface::SORT_FIELD_SUB_STATUS:
                return 's.status';

            default:
                throw new \InvalidArgumentException("Unsupported sort field");
        }
    }
}
