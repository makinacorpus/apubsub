<?php

/**
 * @file
 * Administration pages.
 */

use APubSub\Notification\FormatterInterface;
use APubSub\Notification\ChanTypeInterface;

/**
 * Notification types admin form.
 */
function notification_admin_notification_notification_types($form, &$form_state) {

  $service   = notification_service_get();
  $registry  = $service->getFormatterRegistry();
  $instances = $registry->getAllInstances(true);
  $groups    = $registry->getGroups();

  $label_disabled = t("Disabled");
  $label_enabled  = '<strong>' . t("Enabled") . '</strong>';

  $options = array();
  foreach ($instances as $type => $instance) {
    if ($instance instanceof FormatterInterface) {
      $type  = $instance->getType();
      $group = $instance->getGroupId();

      $options[$type]['description'] = $instance->getDescription();
      $options[$type]['group']       = isset($groups[$group]) ? $groups[$group] : '';
      $options[$type]['type']        = $type;
      $options[$type]['status']      = $service->isTypeEnabled($type) ? $label_enabled : $label_disabled;
    }
  }

  $form['types'] = array(
    '#type'    => 'tableselect',
    '#header'  => array(
      'type'        => t("Type"),
      'group'       => t("Group"),
      'description' => t("Description"),
      'status'      => t("Status"),
    ),
    '#options' => $options,
  );

  $form['actions'] = array(
    '#type'   => 'actions',
  );
  $form['actions']['enable'] = array(
    '#type'   => 'submit',
    '#value'  => t("Enable"),
    '#submit' => array('notification_admin_notification_overview_submit_enable'),
  );
  $form['actions']['disable'] = array(
    '#type'   => 'submit',
    '#value'  => t("Disable"),
    '#submit' => array('notification_admin_notification_overview_submit_disable'),
  );

  return $form;
}

/**
 * Notification types admin form enable selected submit handler.
 */
function notification_admin_notification_overview_submit_enable($form, &$form_state) {

  $types = array();
  foreach ($form_state['values']['types'] as $type => $selected) {
    if ($type === $selected) {
      $types[] = $type;
    }
  }

  if (empty($types)) {
    drupal_set_message(t("Nothing to do"));
    return;
  }

  $disabled = variable_get(APB_VAR_ENABLED_TYPES, array());
  $disabled = array_diff($disabled, $types);

  if (empty($disabled)) {
    variable_set(APB_VAR_ENABLED_TYPES, NULL);
  } else {
    variable_set(APB_VAR_ENABLED_TYPES, array_unique($disabled));
  }

  drupal_set_message(t("Enabled selected types"));
}

/**
 * Notification types admin form disable selected submit handler.
 */
function notification_admin_notification_overview_submit_disable($form, &$form_state) {

  $types = array();
  foreach ($form_state['values']['types'] as $type => $selected) {
    if ($type === $selected) {
      $types[] = $type;
    }
  }

  if (empty($types)) {
    drupal_set_message(t("Nothing to do"));
    return;
  }

  $disabled = variable_get(APB_VAR_ENABLED_TYPES, array());
  $disabled += $types;

  if (empty($disabled)) {
    variable_set(APB_VAR_ENABLED_TYPES, NULL);
  } else {
    variable_set(APB_VAR_ENABLED_TYPES, array_unique($disabled));
  }

  drupal_set_message(t("Disabled selected types"));
}

/**
 * Channel types admin form.
 */
function notification_admin_notification_channel_types($form, &$form_state) {

  $service   = notification_service_get();
  $registry  = $service->getChanTypeRegistry();
  $instances = $registry->getAllInstances(true);
  $groups    = $registry->getGroups();

  $label_disabled = t("Disabled");
  $label_yes      = t("Yes");
  $label_no       = t("No");

  $options = array();
  foreach ($instances as $type => $instance) {
    if ($instance instanceof ChanTypeInterface) {
      $type  = $instance->getType();
      $group = $instance->getGroupId();

      $options[$type]['description'] = $instance->getDescription();
      $options[$type]['group']       = isset($groups[$group]) ? $groups[$group] : '';
      $options[$type]['type']        = $type;
      $options[$type]['visible']     = $instance->isVisible() ? $label_yes : $label_no;
    }
  }

  $form['types'] = array(
    '#type'    => 'tableselect',
    '#header'  => array(
      'type'        => t("Type"),
      'group'       => t("Group"),
      'description' => t("Description"),
      'visible'     => t("Visible for user"),
    ),
    '#options' => $options,
  );

  return $form;
}

/**
 * Global settings form.
 */
function notification_admin_notification_settings($form, &$form_state) {

  $form['interface'] = array(
    '#type'  => 'fieldset',
    '#title' => t("User interface"),
  );
  $form['interface'][APB_VAR_ENABLE_AJAX] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Load and refresh notifications block using AJAX"),
    '#description'   => t("Using AJAX for loading notification block will make your page rendering faster and allow the block to be cached globally for everyone."),
    '#default_value' => variable_get(APB_VAR_ENABLE_AJAX, APB_DEF_ENABLE_AJAX),
  );
  $form['interface'][APB_VAR_UNREAD_UNFOLD] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Auto unmark unread"),
    '#description'   => t("Mark messages as read when unfolding the notification block or viewing the notifications page."),
    '#default_value' => variable_get(APB_VAR_UNREAD_UNFOLD, APB_DEF_UNREAD_UNFOLD),
  );
  $form['interface'][APB_VAR_ENABLE_AJAX . '.default'] = array(
   '#type'         => 'value',
   '#value'        => variable_get(APB_VAR_ENABLE_AJAX, APB_DEF_ENABLE_AJAX),
  );
  $form['interface'][APB_VAR_USER_PAGER_LIMIT] = array(
    '#type'          => 'select',
    '#title'         => t("User notification page limit"),
    '#options'       => drupal_map_assoc(array(5, 10, 20, 30, 50, 100)),
    '#description'   => t("Number of notifications being displayed per page in user notification page."),
    '#default_value' => variable_get(APB_VAR_USER_PAGER_LIMIT, APB_DEF_USER_PAGER_LIMIT),
  );
  $form['interface'][APB_VAR_USER_BLOCK_LIMIT] = array(
    '#type'          => 'select',
    '#title'         => t("User notification block limit"),
    '#options'       => drupal_map_assoc(array(3, 5, 8, 10, 13, 15, 20)),
    '#description'   => t("Number of notifications being displayed per in the user notification block."),
    '#default_value' => variable_get(APB_VAR_USER_BLOCK_LIMIT, APB_DEF_USER_BLOCK_LIMIT),
  );
  $form['interface'][APB_VAR_IMG_STYLE] = array(
    '#type'          => 'select',
    '#title'         => t("Notification image style"),
    '#options'       => image_style_options(TRUE),
    '#description'   => t("Number of notifications being displayed per page in user notification page."),
    '#default_value' => variable_get(APB_VAR_IMG_STYLE, APB_DEF_IMG_STYLE),
  );

  $form['advanced'] = array(
    '#type'  => 'fieldset',
    '#title' => t("Advanced / Performance"),
  );
  $form['advanced'][APB_VAR_ENABLE_PROD] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Enable production mode"),
    '#description'   => t("Will suppress some warnings, errors and exceptions. Always check this option on a production site."),
    '#default_value' => variable_get(APB_VAR_ENABLE_PROD, APB_DEF_ENABLE_PROD),
  );
  $form['advanced'][APB_VAR_CACHE_TYPES] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Cache types definition"),
    '#description'   => t("Do this only if you have <em>a lot</em> of types definition. I don't think anyone will ever click on this checkbox, or (s)he's probably insane."),
    '#default_value' => variable_get(APB_VAR_CACHE_TYPES, APB_DEF_CACHE_TYPES),
  );
  $form['advanced'][APB_VAR_ENABLE_FORMATTED_CONTENT] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Store content inside messages"),
    '#description'   => t("If checked, formatted messages will be sent in the message contents instead of being formatted at view time. This will bypass translation but allows you to display those notifications outside of the originating website. If checked generated links inside formatted notifications will all be absolute. Note that if you change this setting and data has already been sent some notifications might not be displayed correctly."),
    '#default_value' => variable_get(APB_VAR_ENABLE_FORMATTED_CONTENT, APB_DEF_ENABLE_FORMATTED_CONTENT),
  );
  $form['advanced'][APB_VAR_BACKEND] = array(
    '#type'          => 'radios',
    '#title'         => t("Pub/Sub backend to use"),
    '#options'       => apb_backend_list_select_options(),
    '#description'   => t("If you change the backend while site was being used, all current notifications will be left to stall in the older backend, and users won't be able to access it again, except if you switch back to it."),
    '#default_value' => variable_get(APB_VAR_BACKEND, APB_DEF_BACKEND),
  );

  $form = system_settings_form($form);

  $form['#submit'][] = 'notification_admin_notification_settings_submit';

  return $form;
}

/**
 * If variables changed, we need to clear some caches.
 */
function notification_admin_notification_settings_submit($form, &$form_state) {
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
