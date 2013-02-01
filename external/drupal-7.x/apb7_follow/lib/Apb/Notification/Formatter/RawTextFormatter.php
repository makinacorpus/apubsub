<?php

namespace Apb\Notification\Formatter;

use Apb\Notification\AbstractFormatter;
use Apb\Notification\Notification;

class RawTextFormatter extends AbstractFormatter
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\FormatterInterface::format()
     */
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

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\FormatterInterface::getImageURI()
     */
    public function getImageURI(Notification $notification)
    {
        switch ($notification->get('a')) {

            case 'insert':
                return 'icon://filenew';

            case 'update':
                return 'icon://document';

            case 'delete':
                return 'icon://edit-delete';

            default:
                return null;
        }
    }
}
