<?php

namespace Apb\Notification\Formatter;

use Apb\Notification\AbstractFormatter;
use Apb\Notification\Notification;

class EntityFormatter extends AbstractFormatter
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::format()
     */
    public function format(Notification $notification)
    {
        $entityType = $notification->get('type');
        $id         = $notification->get('id');
        $entityInfo = entity_get_info($entityType);
        $entities   = entity_load($entityType, array($id));

        if (empty($entities)) {
            $title           = t("unknown");
            $typeLabel       = t("object");
        } else {
            $entity          = array_shift($entities);
            list(,, $bundle) = entity_extract_ids($entityType, $entity);
            $title           = entity_label($entityType, $entity);
            $typeLabel       = $entityInfo['bundles'][$bundle]['label'];
        }

        if (!($uid = $notification->get('uid')) || !($account = user_load($uid))) {
            $account = drupal_anonymous_user();
        }
        // theme_username() is stupid and doesn't let us controler whether or
        // not we want the link over the username. Problem is that themes or
        // custom sites might utilize this method to output something else
        // than the $account->name property
        $accountTitle = theme('username', array(
            'account' => $account,
        ));

        $tVariables = array(
            '%username' => strip_tags($accountTitle),
            '!title'    => $title,
            '@type'     => $typeLabel,
        );

        if (($uri = entity_uri($entityType, $entity)) && isset($uri['path'])) {
            $tVariables['!title'] = l($tVariables['!title'], $uri['path']);
        }

        switch ($notification->get('a')) {

            case 'insert':
                return t("%username created the !title @type", $tVariables);

            case 'update':
                return t("%username modified the !title @type", $tVariables);

            case 'delete':
                return t("%username deleted the !title @type", $tVariables);

            default:
                return t("%username did something to the !title @type", $tVariables);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::getImageURI()
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
