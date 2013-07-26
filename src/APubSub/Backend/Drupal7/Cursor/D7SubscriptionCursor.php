<?php

namespace APubSub\Backend\Drupal7\Cursor;

use APubSub\Field;

/**
 * Drupal 7 implementation of subscription cursor
 */
class D7SubscriptionCursor extends AbstractD7Cursor
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            Field::SUB_ID,
            Field::SUB_CREATED_TS,
            Field::SUB_STATUS,
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
        return $this->getContext()->getBackend()->getSubscription($id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObjects()
     */
    protected function loadObjects($idList)
    {
        return $this->getContext()->getBackend()->getSubscriptions($idList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::getSortColumn()
     */
    protected function getSortColumn($sort)
    {
        switch ($sort) {

            case Field::SUB_ID:
                return 's.id';

            case Field::SUB_CREATED_TS:
                return 's.created';

            case Field::SUB_STATUS:
                return 's.status';

            default:
                throw new \InvalidArgumentException("Unsupported sort field");
        }
    }
}
