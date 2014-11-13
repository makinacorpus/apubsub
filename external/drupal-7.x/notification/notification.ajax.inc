<?php

use APubSub\Field;

/**
 * @file
 * AJAX callbacks.
 */

/**
 * AJAX callback, see own user notifications.
 */
function notification_ajax_user_notifications() {
  global $user;

  $content = notification_block_render($user, true);

  if (is_array($content)) {
    $build['since_id'] = empty($content['#list']) ? 0 : max(array_keys($content['#list']));
    $build['html'] = drupal_render($content);
    drupal_json_output($build);
  }

  // This will force Drupal to write all its caches and ensure next
  // hits will actually do something smart.
  drupal_page_footer();

  exit;
}

/**
 * AJAX callback, unmark messages as unread when receiving this.
 */
function notification_ajax_unfold_notifications() {
  global $user;

  notification_service_get()
    ->getSubscriber('u:' . $user->uid)
    ->fetch(array(
      Field::MSG_ID => array('<=' => $_POST['since_id']),
    ))
    ->update(array(
      Field::MSG_UNREAD => 0,
    ));
}
