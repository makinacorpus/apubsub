<?php

namespace APubSub\Backend\Drupal7\Helper;

use APubSub\Helper\ListInterface;

/**
 * Drupal 7 implementation of subscription list helper
 */
class D7SubscriberList extends AbstractD7List
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Helper\ListInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            ListInterface::SORT_FIELD_ID,
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
        return $this->context->backend->getSubscriber($id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObjects()
     */
    protected function loadObjects($idList)
    {
        $ret = array();

        foreach ($idList as $id) {
            $ret[] = $this->context->backend->getSubscriber($id);
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
            case ListInterface::SORT_FIELD_ID:
                return 'mp.name';

            default:
                throw new \InvalidArgumentException("Unsupported sort field");
        }
    }
}
