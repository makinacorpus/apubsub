<?php

namespace APubSub\Backend\Drupal7\Cursor;

use APubSub\CursorInterface;

/**
 * Drupal 7 implementation of channel cursor
 */
class D7ChannelCursor extends AbstractD7Cursor
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\CursorInterface::getAvailableSorts()
     */
    public function getAvailableSorts()
    {
        return array(
            CursorInterface::FIELD_ID,
            CursorInterface::FIELD_CREATED,
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
        return $this->getContext()->getBackend()->getChannelByDatabaseId($id);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::loadObjects()
     */
    protected function loadObjects($idList)
    {
        return $this->getContext()->getBackend()->getChannelsByDatabaseId($idList);
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Backend\Drupal7\Helper\AbstractD7List::getSortColumn()
     */
    protected function getSortColumn($sort)
    {
        switch ($sort) {

            case CursorInterface::FIELD_ID:
                return 'c.name';

            case CursorInterface::FIELD_CREATED:
                return 'c.created';

            default:
                throw new \InvalidArgumentException("Unsupported sort field");
        }
    }
}
