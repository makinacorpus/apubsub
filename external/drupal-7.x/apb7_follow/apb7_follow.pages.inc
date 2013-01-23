<?php

/**
 * @file
 * User pages.
 */

use APubSub\CursorInterface;
use APubSub\Error\MessageDoesNotExistException;

use Apb\Notification\NotificationManager;

/**
 * User notifications page.
 */
function apb7_notification_page_user_list($form, &$form_state, $account) {

  $manager     = apb7_notification_manager_get();
  $backend     = $manager->getBackend();
  $subscriber  = $manager->getSubscriberFor($account->uid);
  $displayed   = 0;
  $total       = 0;
  $limit       = variable_get(APB_VAR_USER_PAGER_LIMIT, APB_DEF_USER_PAGER_LIMIT);
  $element     = PagerDefault::$maxElement++;
  $offset      = pager_find_page($element) * $limit;

  $cursor = $subscriber
    ->fetch()
    ->addSort(
        CursorInterface::FIELD_MSG_SENT,
        CursorInterface::SORT_DESC)
    ->setRange($limit, $offset);

  $total = $cursor->getTotalCount();
  pager_default_initialize($total, $limit);

  $form['#tree']  = TRUE;
  $form['#theme'] = 'notifications_page';
  $form['list'] = array(
    '#type'    => 'checkboxes',
    '#options' => array(),
  );
  $form['uid'] = array(
    '#type'  => 'value',
    '#value' => $account->uid,
  );

  foreach ($cursor as $message) {
    $notification = $manager->getNotification($message);

    if (!$notification->isValid()) {
      continue;
    }

    ++$displayed;

    if ($uri = $notification->getImageUri()) {
      $image = apb7_follow_image_render($uri);
    } else {
      $image = '';
    }

    $form['list']['#options'][$message->getId()] = array(
      'image'  => $image,
      'time'   => $message->getSendTimestamp(),
      'unread' => $message->isUnread(),
      'text'   => $notification->format(),
    );
  }

  $form['top']['#markup'] = format_plural(
    $total,
    "Displaying @displayed of @count notification",
    "Displaying @displayed of @count notifications",
    array('@displayed' => $displayed));

  $form['pager']['#markup'] = theme('pager');

  $form['actions'] = array(
    '#type'  => 'actions',
    'unread' => array(
      '#type'   => 'submit',
      '#value'  => t("Mark as unread"),
      '#submit' => array('apb7_notification_page_user_list_submit_unread'),
    ),
    'delete' => array(
      '#type'  => 'submit',
      '#value' => t("Delete"),
      '#submit' => array('apb7_notification_page_user_list_submit_delete'),
    ),
    'preferences' => array(
      '#type'  => 'link',
      '#title' => t("Preferences"),
      '#href'  => 'user/' . $account->uid . '/notifications/settings',
    ),
  );

  return $form;
}

/**
 * User notifications page mark as unread submit handler.
 */
function apb7_notification_page_user_list_submit_unread($form, &$form_state) {

  $manager    = apb7_notification_manager_get();
  $subscriber = $manager->getSubscriberFor($form_state['values']['uid']);
  $count      = 0;

  foreach ($form_state['values']['list'] as $msgid => $enabled) {
    if ($msgid == $enabled) {
      ++$count;

      try {
        $subscriber
          ->getMessage($msgid)
          ->setUnread(false);
      } catch (MessageDoesNotExistException $e) {
        // Silent.
      }
    }
  }

  drupal_set_message(format_plural($count,
    "@count message marked as unread",
    "@count messages marked as unread"));
}

/**
 * User notifications page delete submit handler.
 */
function apb7_notification_page_user_list_submit_delete($form, &$form_state) {

  $manager    = apb7_notification_manager_get();
  $subscriber = $manager->getSubscriberFor($form_state['values']['uid']);
  $count      = 0;
  $list       = array();

  foreach ($form_state['values']['list'] as $msgid => $enabled) {
    if ($msgid == $enabled) {
      ++$count;
      $list[] = $msgid;
    }
  }

  $subscriber->deleteMessages($list);

  drupal_set_message(format_plural($count,
    "@count message deleted",
    "@count messages deleted"));
}

/**
 * User notifications preference page.
 */
function apb7_notification_page_user_settings($form, &$form_state, $account) {

  return $form;
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_notifications_page(&$variables) {

  $form = &$variables['form'];

  foreach (element_children($form['list']) as $key) {
    // Should have already been expanded.
    // Sad checkbox is sad.
    $item = &$form['list'][$key]['#title'];
    unset($form['list'][$key]['#title']);

    $item['checkbox'] = drupal_render($form['list'][$key]);

    $variables['list'][] = $item;
  }

  $variables['top']   = drupal_render($form['top']);
  $variables['pager'] = drupal_render($form['pager']);
  $variables['form']  = drupal_render_children($form);
}
