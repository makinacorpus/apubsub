<?php

namespace APubSub\Backend\Memory;

use APubSub\Backend\DefaultMessage;

/**
 * Specific memory implementatation that will not trigger any calls over the
 * subscription when changing the message read status, since everything is
 * stored into memory
 */
class MemoryMessage extends DefaultMessage
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\MessageInterface::setReadStatus()
     */
    public function setUnread($toggle = false)
    {
        if ($this->unread !== $toggle) {
            $this->unread = $toggle;

            if ($toggle) {
                $this->readTimestamp = null;
            } else {
                $this->readTimestamp = time();
            }
        }
    }
}
