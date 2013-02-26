<?php

namespace APubSub\Backend\Memory;

use APubSub\CursorInterface;

/**
 * Helper class that will be able to sort a message array for usage as sort
 * callback into the ArrayCursor class
 */
class MemoryMessageUpdater
{
    public function apply(MemoryMessage $message, $values)
    {
        foreach ($values as $field => $value) {
            switch ($field) {

                case CursorInterface::FIELD_MSG_UNREAD:
                    $message->setUnread((bool) $value);
                    break; 
            }
        }
    }
}
