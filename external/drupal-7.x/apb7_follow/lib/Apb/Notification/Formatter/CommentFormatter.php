<?php

namespace Apb\Notification\Formatter;

use Apb\Notification\AbstractFormatter;
use Apb\Notification\Notification;

class CommentFormatter extends AbstractFormatter
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::format()
     */
    public function format(Notification $notification)
    {
        $cid     = $notification->get('c');
        $comment = comment_load($cid);

        if (false === $comment) {
          // Comment is being deleted
          $comment          = new \stdClass();
          $comment->subject = $notification->get('s');
          $comment->nid     = $notification->get('n');
          $comment->uid     = $notification->get('u');
        }

        if (!$account = user_load($comment->uid)) {
            $account = drupal_anonymous_user();
        }

        if (!$node = node_load($comment->nid)) {
            $node        = new \stdClass();
            $node->title = t("Deleted node");
        }

        // theme_username() is stupid and doesn't let us controler whether or
        // not we want the link over the username. Problem is that themes or
        // custom sites might utilize this method to output something else
        // than the $account->name property
        $accountTitle = theme('username', array(
            'account' => $account,
        ));

        $tVariables = array(
            '!username'     => strip_tags($accountTitle),
            '!commenttitle' => strip_tags($comment->subject),
            '!title'        => strip_tags($node->title),
        );

        if ($account->uid && ($uri = entity_uri('user', $account)) && isset($uri['path'])) {
            $tVariables['!username'] = l($tVariables['!username'], $uri['path']);
        }
        if ($node && ($uri = entity_uri('node', $node)) && isset($uri['path'])) {
            $tVariables['!title'] = l($tVariables['!title'], $uri['path']);
        }
        if (isset($comment->cid) && ($uri = entity_uri('comment', $comment)) && isset($uri['path'])) {
            $tVariables['!commenttitle'] = l($tVariables['!commenttitle'], $uri['path']);
        }

        switch ($notification->get('a')) {

            case 'insert':
                return t("!username commented !title: !commenttitle", $tVariables);

            case 'update':
                return t("!username's comment !commenttitle has been modified on !title", $tVariables);

            case 'delete':
                return t("!username's comment has been deleted on !title", $tVariables);
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
                return 'icon://comment';

            case 'update':
                return 'icon://comment';

            case 'delete':
                return 'icon://comment';
        }
    }
}
