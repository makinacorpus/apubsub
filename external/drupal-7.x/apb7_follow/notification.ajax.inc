<?php

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
