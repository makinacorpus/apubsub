<?php

namespace MakinaCorpus\APubSub\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\Notification;

class RawTextFormatter extends AbstractFormatter
{
    public function format(Notification $notification)
    {
        $stringList = array();

        $data = $notification->getData();
        if (!empty($data)) {
            foreach ($data as $text) {
                $stringList[] = (string)$text;
            }

            return implode('<br/>', $stringList);
        } else {
            return t("Something happened.");
        }
    }
}
