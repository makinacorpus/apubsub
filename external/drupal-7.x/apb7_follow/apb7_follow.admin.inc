<?php

/**
 * @file
 * Administration pages.
 */

/**
 * Main overview admin form.
 */
function apb7_follow_admin_notification_overview($form, &$form_state) {
  return $form;
}

/**
 * Global settings form.
 */
function apb7_follow_admin_notification_settings($form, &$form_state) {

  $form[APB_VAR_ENABLE_AJAX] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Load and refresh notifications block using AJAX"),
    '#description'   => t("Using AJAX for loading notification block will make your page rendering faster and allow the block to be cached globally for everyone."),
    '#default_value' => variable_get(APB_VAR_ENABLE_AJAX, APB_DEF_ENABLE_AJAX),
  );
  $form[APB_VAR_ENABLE_AJAX . '.default'] = array(
     '#type'         => 'value',
     '#value'        => variable_get(APB_VAR_ENABLE_AJAX, APB_DEF_ENABLE_AJAX),
  );

  $form[APB_VAR_ENABLE_PROD] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Enable production mode"),
    '#description'   => t("Will suppress some warnings and error, and use minified JavaScript files. Always check this option on a production site."),
    '#default_value' => variable_get(APB_VAR_ENABLE_PROD, APB_DEF_ENABLE_PROD),
  );

  $form = system_settings_form($form);

  $form['#submit'][] = 'apb7_follow_admin_notification_settings_submit';

  return $form;
}

/**
 * If variables changed, we need to clear some caches.
 */
function apb7_follow_admin_notification_settings_submit($form, &$form_state) {
  $values = $form_state['values'];

  if ($values[APB_VAR_ENABLE_AJAX] != $values[APB_VAR_ENABLE_AJAX . '.default']) {
    // Variable has changed, we need to clear block info cache
    db_update('block')
      ->fields(array(
        'cache' => $values[APB_VAR_ENABLE_AJAX] ? DRUPAL_CACHE_GLOBAL : DRUPAL_NO_CACHE,
      ))
      ->condition('module')
      ->condition('delta')
      ->execute();

    // Drop block and page cache.
    cache_clear_all();
  }
}
