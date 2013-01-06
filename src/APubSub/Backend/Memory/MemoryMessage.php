<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\DefaultMessage;

class MemoryMessage extends DefaultMessage
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::setReadStatus()
     */
    public function setUnread($toggle = false)
    {
        $this->unread = $toggle;
    }
}
