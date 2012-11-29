<?php

namespace APubSub\Backend\Drupal7\Cursor;

use APubSub\CursorInterface;

/**
 * Drupal 7 implementation of subscription cursor
 */
class D7SubscriberCursor extends AbstractD7Cursor
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            CursorInterface::FIELD_ID,
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
            ->select('apb_sub_map', 'mp')
            ->fields('mp', array('name'))
            ->groupBy('mp.name');
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObject()
     */
    protected function loadObject($id)
    {
        return $this->getContext()->getBackend()->getSubscriber($id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObjects()
     */
    protected function loadObjects($idList)
    {
        $ret = array();

        foreach ($idList as $id) {
            $ret[] = $this->getContext()->getBackend()->getSubscriber($id);
        }

        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::getSortColumn()
     */
    protected function getSortColumn($sort)
    {
        switch ($sort)
        {
            case CursorInterface::FIELD_ID:
                return 'mp.name';

            default:
                throw new \InvalidArgumentException("Unsupported sort field");
        }
    }
}
