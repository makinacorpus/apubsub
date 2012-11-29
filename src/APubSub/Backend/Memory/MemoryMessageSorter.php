<?php

namespace APubSub\Backend\Memory;

use APubSub\CursorInterface;

/**
 * Helper class that will be able to sort a message array for usage as sort
 * callback into the ArrayCursor class
 */
class MemoryMessageSorter
{
    public function getAvailableSorts()
    {
        return array(
            CursorInterface::FIELD_CHAN_ID,
            CursorInterface::FIELD_MSG_ID,
            CursorInterface::FIELD_MSG_SENT,
            CursorInterface::FIELD_MSG_UNREAD,
            CursorInterface::FIELD_SUB_ID,
        );
    }

    public function __invoke(&$array, $sorts)
    {
        uasort($array, function ($a, $b) use ($sorts) {

            $value = 0;

            // All backends must sort by id per default so let's ensure we are
            // going to do it in case no sort fields are specified
            if (empty($sorts)) {
                $sorts[CursorInterface::FIELD_MSG_SENT] = CursorInterface::SORT_ASC;
            }

            foreach ($sorts as $field => $direction) {
                switch ($field) {

                    case CursorInterface::FIELD_CHAN_ID:
                        $value = strcmp($a->getChannelId(), $b->getChannelId());
                        break;

                    case CursorInterface::FIELD_MSG_ID:
                    case CursorInterface::FIELD_MSG_SENT:
                        $value = $a->getId() - $b->getId();
                        break;


                    case CursorInterface::FIELD_MSG_UNREAD:
                        $value = ((int)$a->isUnread()) - ((int)$b->isUnread());
                        break;

                    case CursorInterface::FIELD_SUB_ID:
                        $value = $a->getSubscriptionId() - $b->getSubscriptionId();
                        break;
                }

                if (0 !== $value) {
                    if (CursorInterface::SORT_DESC === $direction) {
                        $value = 0 - $value;
                    }

                    return $value;
                }
            }

            return $value;
        });
    }
}
