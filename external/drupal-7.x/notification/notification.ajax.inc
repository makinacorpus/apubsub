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

  $content = notification_notification_render_block($user, true);

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
function notification_ajax_unfold_notifications() {
  global $user;

  notification_service_get()
    ->getSubscriber($user->uid)
    ->update(array(
      Field::MSG_UNREAD => false,
    ));
}
