<?php

namespace APubSub\Backend\Drupal7\Helper;

use APubSub\Helper\ListInterface;

/**
 * Drupal 7 implementation of channel list helper
 */
class D7ChannelList extends AbstractD7List
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
            ->select('apb_chan', 'c')
            ->fields('c', array('id'));
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObject()
     */
    protected function loadObject($id)
    {
        return $this->context->backend->getChannel($id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObjects()
     */
    protected function loadObjects($idList)
    {
        return $this->context->backend->getChannels($idList);
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
                return 'c.name';

            case ListInterface::SORT_FIELD_CREATED:
                return 'c.created';

            default:
                throw new \InvalidArgumentException("Unsupported sort field");
        }
    }
}
