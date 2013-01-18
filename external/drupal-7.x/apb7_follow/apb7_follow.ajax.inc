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

  drupal_exit();
}
