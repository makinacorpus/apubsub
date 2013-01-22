<?php

/**
 * @file
 * Administration pages.
 */

use Apb\Notification\FormatterInterface;

/**
 * Main overview admin form.
 */
function apb7_follow_admin_notification_overview($form, &$form_state) {

  $instances = apb7_notification_manager_get()->getTypeRegistry()->getAllInstances();

  $options = array();
  foreach ($instances as $type => $instance) {
    if ($instance instanceof FormatterInterface) {
      $options[$type]['description'] = $instance->getDescription();
      $options[$type]['type']        = $instance->getType();
    }
  }

  $form['types'] = array(
    '#type'    => 'tableselect',
    '#header'  => array(
      'type'        => t("Type"),
      'description' => t("Description"),
      'status'      => t("Status"),
    ),
    '#options' => $options,
  );

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

  $form[APB_VAR_ENABLE_FORMATTED_CONTENT] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Store content inside messages"),
    '#description'   => t("If checked, formatted messages will be sent in the message contents instead of being formatted at view time. This will bypass translation but allows you to display those notifications outside of the originating website. If checked generated links inside formatted notifications will all be absolute. Note that if you change this setting and data has already been sent some notifications might not be displayed correctly."),
    '#default_value' => variable_get(APB_VAR_ENABLE_FORMATTED_CONTENT, APB_DEF_ENABLE_FORMATTED_CONTENT),
  );

  $form[APB_VAR_BACKEND] = array(
    '#type'          => 'radios',
    '#title'         => t("Pub/Sub backend to use"),
    '#options'       => apb7_backend_list_select_options(),
    '#description'   => t("If you change the backend while site was being used, all current notifications will be left to stall in the older backend, and users won't be able to access it again, except if you switch back to it."),
    '#default_value' => variable_get(APB_VAR_BACKEND, APB_DEF_BACKEND),
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
