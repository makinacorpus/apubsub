<?php

use APubSub\CursorInterface;
/**
 * @file
 * AJAX callbacks.
 */

/**
 * AJAX callback, see own user notifications.
 */
function apb7_follow_ajax_user_notifications() {
  global $user;

  $content = apb7_follow_notification_render_block($user, true);

  if (is_array($content)) {
    echo drupal_render($content);
  }

  // This will force Drupal to write all its caches and ensure next
  // hits will actually do something smart.
  drupal_page_footer();

  exit;
}

/**
 * AJAX callback, unmark messages as unread when receiving this.
 */
function apb7_follow_ajax_unfold_notifications() {
  global $user;

  notification_service_get()
    ->getSubscriber($user->uid)
    ->update(array(
      CursorInterface::FIELD_MSG_UNREAD => false,
    ));
}
