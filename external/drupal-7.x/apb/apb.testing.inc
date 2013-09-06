<?php

/**
 * @file
 * APubSub testing code.
 */

use APubSub\Field;

/**
 * Psuedo implementation of hook_menu() when testing is enabled.
 */
function apb_testing_menu() {
  $items = array();

  // Testing stuff.
  $items['admin/messages'] = array(
    'title' => 'Messages (testing)',
    'description' => 'Play with and test APubSub API.',
    'page callback' => 'system_admin_menu_block_page',
    'access arguments' => array('access administration pages'),
    'file' => 'system.admin.inc',
    'file path' => drupal_get_path('module', 'system'),
    'type' => MENU_NORMAL_ITEM,
  );

  // Stress test UI.
  $items['admin/messages/stress'] = array(
    'title' => 'Massive stress test',
    'description' => 'Multistep interactive wizard to proceed to a massive stress test.',
    'page callback' => 'apb_testing_volume_list',
    'access arguments' => array('access administration pages'),
    'file' => 'apb.testing.inc',
    'type' => MENU_NORMAL_ITEM,
  );
  $items['admin/messages/stress/add'] = array(
    'title' => 'Add scenario',
    'description' => 'Create new test scenario.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('apb_testing_volume_form'),
    'access arguments' => array('access administration pages'),
    'file' => 'apb.testing.inc',
    'type' => MENU_LOCAL_ACTION,
  );
  $items['admin/messages/stress/%apb_test_scenario'] = array(
    'title' => 'View scenario',
    'page callback' => 'apb_testing_volume_view_page',
    'page arguments' => array(3),
    'access arguments' => array('access administration pages'),
    'file' => 'apb.testing.inc',
    'type' => MENU_CALLBACK | MENU_VISIBLE_IN_BREADCRUMB,
  );
  $items['admin/messages/stress/%apb_test_scenario/populate-channels'] = array(
    'title' => 'Populate channels',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('apb_testing_volume_scenario_populate_channels_form', 3),
    'access arguments' => array('access administration pages'),
    'file' => 'apb.testing.inc',
    'type' => MENU_LOCAL_ACTION,
  );
  $items['admin/messages/stress/%apb_test_scenario/populate-messages'] = array(
    'title' => 'Populate messages',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('apb_testing_volume_scenario_populate_messages_form', 3),
    'access arguments' => array('access administration pages'),
    'file' => 'apb.testing.inc',
    'type' => MENU_LOCAL_ACTION,
  );
  $items['admin/messages/stress/%apb_test_scenario/delete'] = array(
    'title' => 'Delete',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('apb_testing_volume_scenario_delete_form', 3),
    'access arguments' => array('access administration pages'),
    'file' => 'apb.testing.inc',
    'type' => MENU_LOCAL_ACTION,
  );

  return $items;
}

/**
 * List test scenarios.
 */
function apb_testing_volume_list() {

  $header = array(
    array('data' => t("Id"),      'field' => 's.id'),
    array('data' => t("Name"),    'field' => 's.name'),
    array('data' => t("Created"), 'field' => 's.created'),
  );

  $rows = array();

  $scenarios = db_select('apb_test_scenario', 's')
    ->fields('s')
    ->extend('PagerDefault')
    ->extend('TableSort')
    ->limit(20)
    ->orderByHeader($header)
    ->execute()
    ->fetchAll();

  foreach ($scenarios as $scenario) {
    $scenario->data = unserialize($scenario->data);

    $rows[] = array(
      $scenario->id,
      l(check_plain($scenario->name), 'admin/messages/stress/' . $scenario->id),
      format_date($scenario->created),
    );
  }

  return theme('table', array(
    'header' => $header,
    'rows'   => $rows,
  )) . theme('pager');
}

/**
 * Add new test scenario form.
 */
function apb_testing_volume_form($form, &$form_state) {
  $form['#tree'] = TRUE;

  if (!isset($form_state['storage'])) {
    $form_state['storage'] = array();
  }

  $storage = &$form_state['storage'];

  // Set default values for everything that will be used during this import
  // Consider that we have a use case for this scenario: a social network
  // notification system.
  $storage += array(
    'name'                => 'Social network',
    'backend'             => '\APubSub\Backend\Drupal7\D7Backend',
    'name_prefix'         => 'c:' . uniqid() . ':',
    'user_count'          => 10000,  // Number of users of this network.
    'per_user_chan_count' => 2,      // Each user is a certain number of channel
                                     // (for exemple, private and public).
    'avg_msg_per_chan'    => 10,     // Average of 10 messages per chan per day.
    'avg_user_sub_count'  => 15,     // Each user has 15 friends.
    'avg_user_chan_count' => 0.5 ,   // Each user subscribes to a 50% average of
                                     // its friends channels (public or
                                     // private).
    'sub_fetch_rate'      => 0.3,    // A subscriber connects 1 day out of 3.
    'day_count'           => 365,    // One year stress test per default.
  );

  $form['help'] = array(
    '#prefix' => '<p>',
    '#suffix' => '</p>',
    '#markup' => t("We are going to run a <em>social network simulation scenario</em>." .
       " Each day passing will generate a certain amount of notifications, in a certain" .
       " amount of distribution channels, thus stressing the system a bit."),
  );

  $form['data']['name'] = array(
    '#type' => 'textfield',
    '#title' => t("Scenario name"),
    '#default_value' => $storage['name'],
    '#required' => TRUE,
  );
  $form['data']['backend'] = array(
    '#type' => 'select',
    '#title' => t("Select backend to stress"),
    '#options' => array(
      'default' => t("Default"),
    ),
    '#default_value' => '\APubSub\Backend\Drupal7\D7Backend',
    '#required' => TRUE,
    '#description' => t("Only the database backend is supported at the moment."),
  );
  $form['data']['user_count'] = array(
    '#type' => 'textfield',
    '#title' => t("User count"),
    '#default_value' => $storage['user_count'],
    '#required' => TRUE,
    '#description' => t("Total number of users in the system"),
    '#size' => 10,
  );
  $form['data']['per_user_chan_count'] = array(
    '#type' => 'textfield',
    '#title' => t("Channel per user count"),
    '#default_value' => $storage['per_user_chan_count'],
    '#required' => TRUE,
    '#description' => t("For each user, a channel will be for example: <em>private messages</em>, <em>public messages</em>, <em>recent likes</em> etc..."),
    '#size' => 10,
  );
  $form['data']['avg_msg_per_chan'] = array(
    '#type' => 'textfield',
    '#title' => t("Average number of messages per channel and per day"),
    '#default_value' => $storage['avg_msg_per_chan'],
    '#required' => TRUE,
    '#description' => t("Each day passing, this number of messages will be sent in each channel."),
    '#size' => 10,
  );
  $form['data']['avg_user_sub_count'] = array(
    '#type' => 'textfield',
    '#title' => t("Average friend per user count"),
    '#default_value' => $storage['avg_user_sub_count'],
    '#required' => TRUE,
    '#description' => t("Each user has as many subscriptions as he has friends times the number of channel per friend. This number is the friend count number (not the real subscriptions number)."),
    '#size' => 10,
  );
  $form['data']['avg_user_chan_count'] = array(
    '#type' => 'select',
    '#title' => t("Average friend channel subscription"),
    '#options' => array(
      // Array keys don't like float, they'll be casted as ints.
      "0.1"  => "10%",
      "0.15" => "15%",
      "0.3"  => "30%",
      "0.5"  => "50%",
      "0.8"  => "80%",
      "1"    => "100%"
    ),
    '#default_value' => $storage['avg_user_chan_count'],
    '#required' => TRUE,
    '#description' => t("Each user will subscribe an average of this percentage of all his friend channels."),
  );
  $form['data']['sub_fetch_rate'] = array(
    '#type' => 'select',
    '#title' => t("Fetch rate"),
    '#options' => array(
      // Array keys don't like float, they'll be casted as ints.
      "0.1"  => "10%",
      "0.15" => "15%",
      "0.3"  => "30%",
      "0.5"  => "50%",
      "0.8"  => "80%",
      "1"    => "100%"
    ),
    '#default_value' => $storage['sub_fetch_rate'],
    '#required' => TRUE,
    '#description' => t("Percentage per day and per user of chances they will connect to the application and read their notifications."),
  );
  $form['data']['day_count'] = array(
    '#type' => 'textfield',
    '#title' => t("Day count"),
    '#default_value' => $storage['day_count'],
    '#required' => TRUE,
    '#description' => t("Run this simulation for the specified number of days."),
    '#size' => 10,
  );

  $form['actions'] = array(
    '#type' => 'actions',
    'submit' => array(
      '#type' => 'submit',
      '#value' => t("Create test"),
    ),
  );

  return $form;
}

function apb_testing_volume_form_submit($form, &$form_state) {
  $storage = &$form_state['storage'];

  // Ensure values.
  foreach ($form_state['values']['data'] as $key => $value) {
    if (array_key_exists($key, $storage)) {
      $storage[$key] = $value;
    }
  }

  db_insert('apb_test_scenario')
    ->fields(array(
      'name' => $storage['name'],
      'created' => time(),
      'data' => serialize($storage),
    ))
    ->execute();

  $seq = ($cx->driver() === 'pgsql') ? 'apb_test_scenario_id_seq' : null;
  $id = (int)$cx->lastInsertId($seq);

  drupal_set_message(t("Scenario %name has been created.", array(
    '%name' => check_plain($storage['name']),
  )));

  $form_state['redirect'] = 'admin/messages/stress/' . $id;
}

/**
 * Single scenario overview page.
 */
function apb_testing_volume_view_page($scenario) {
  drupal_set_title(t("Scenario @name", array(
    '@name' => $scenario->name,
  )));

  return array('#markup' => "@todo");
}

/**
 * Single scenario populate form.
 */
function apb_testing_volume_scenario_populate_channels_form($form, &$form_state, $scenario) {
  $form['id'] = array('#type' => 'value', '#value' => $scenario->id);

  $question = t("Drop all data from backend and populate with this scenario data?");
  $path = 'admin/messages/stress/' . $scenario->id;

  return confirm_form($form, $question, $path);
}

/**
 * Single scenario populate form submit handler.
 */
function apb_testing_volume_scenario_populate_channels_form_submit($form, &$form_state) {

  $scenario = apb_test_scenario_load($form_state['values']['id']);

  // FIXME: MySQL and Drupal7 backend only
  db_query("TRUNCATE {batch}");
  db_query("TRUNCATE {queue}");
  db_query("TRUNCATE {apb_queue}");
  db_query("TRUNCATE {apb_msg}");
  db_query("ALTER TABLE {apb_msg} AUTO_INCREMENT=0");
  db_query("TRUNCATE {apb_sub_map}");
  db_query("TRUNCATE {apb_sub}");
  db_query("TRUNCATE {apb_chan}");
  db_query("ALTER TABLE {apb_chan} AUTO_INCREMENT=0");

  batch_set(array(
    'operations' => array(
      array('_apb_testing_volume_batch_setup', array($scenario->data)),
    ),
    'title' => 'Creating channels and subscriptions',
    'finished' => 'update_finished',
    'file' => drupal_get_path('module', 'apb') . '/apb.testing.inc',
  ));
}

/**
 * Single scenario populate form.
 */
function apb_testing_volume_scenario_populate_messages_form($form, &$form_state, $scenario) {
  $form['id'] = array('#type' => 'value', '#value' => $scenario->id);

  $form['day_count'] = array(
    '#type' => 'textfield',
    '#title' => t("Number of day to process"),
    '#default_value' => $scenario->data['day_count'],
    '#size' => 10,
  );

  $question = t("Inject messages");
  $path = 'admin/messages/stress/' . $scenario->id;

  return confirm_form($form, $question, $path);
}

/**
 * Single scenario populate form submit handler.
 */
function apb_testing_volume_scenario_populate_messages_form_submit($form, &$form_state) {

  $scenario = apb_test_scenario_load($form_state['values']['id']);

  batch_set(array(
    'operations' => array(
      array('_apb_testing_volume_batch_messages', array($scenario->data)),
    ),
    'title' => 'Creating messages',
    'finished' => 'update_finished',
    'file' => drupal_get_path('module', 'apb') . '/apb.testing.inc',
  ));
}
/**
 * Single scenario delete form.
 */
function apb_testing_volume_scenario_delete_form($form, &$form_state, $scenario) {
  $form['id'] = array('#type' => 'value', '#value' => $scenario->id);

  $question = t("Drop this scenario (data will be left in the backend)?");
  $path = 'admin/messages/stress/' . $scenario->id;

  return confirm_form($form, $question, $path);
}

/**
 * Single scenario delete form submit.
 */
function apb_testing_volume_scenario_delete_form_submit($form, &$form_state) {
  db_query("DELETE FROM {apb_test_scenario} WHERE id = :id", array(
    ':id' => $form_state['values']['id'],
  ));
  drupal_set_message(t("Scenario has been deleted."));
  $form_state['redirect'] = 'admin/messages/stress';
}

/**
 * Batch operation that will populate data for a scenario.
 */
function _apb_testing_volume_batch_setup($storage, &$context) {

  if (empty($context['sandbox'])) {
    // We cannot create it all, the max value will be approximative.
    $context['sandbox']['progress'] = 0;
    // Afraid are you?
    $context['sandbox']['max'] = $storage['user_count'] * $storage['per_user_chan_count'];
  }

  $sandbox = &$context['sandbox'];

  // Number of chan per iteration, this is also the number of subscriptions
  // since a chan is also a user
  $chanLimit   = 100;
  $subscribers = array();
  // First channel id for this iteration
  $startAt     = $sandbox['progress'] + 1;
  // Number of subscriptions per channel
  $subCount    = ceil($storage['avg_user_sub_count'] * $storage['per_user_chan_count'] * $storage['avg_user_chan_count']);

  // Do not bypass maximum count
  if (($sandbox['max'] - $sandbox['progress']) < $chanLimit) {
    $chanLimit = $sandbox['max'] - $sandbox['progress'];
  }

  $backend = apb_backend_get($storage['backend']);

  // Create all chans one shot
  $chanlist = array();
  for ($i = 0; $i < $chanLimit; ++$i) {
    $chanlist[] = $startAt + $i;
  }
  $chans = $backend->createChannels($chanlist);

  // Set the progress right away
  $sandbox['progress'] += $chanLimit;

  // Create subscribers
  foreach ($chans as $chan) {
    $subscribers[] = $backend->getSubscriber('user:' . $chan->getId());
  }
  // Create a decal between channels and subscribers so that a channel
  // subscriber will never subscribe for himself
  array_unshift($subscribers, array_pop($subscribers));

  // Create subscriptions for channels
  foreach ($chans as $chan) {
    $chanId = $chan->getId();
    for ($j = 0; $j < $subCount; ++$j) {
      // Select the right subscriber (anyone that is not the channel) and
      // make him subscribe to the channel
      $index = (($chanId + $j) % $chanLimit);
      $subscribers[$index]->subscribe($chanId);
    }
  }

  if ($sandbox['max'] < $sandbox['progress']) {
    // Batch is finished
    $context['finished'] = 1;
  } else {
    if (0.99999 < ($context['finished'] = ($sandbox['progress'] / $sandbox['max']))) {
      $context['finished'] = 0.99999;
    }
  }
}

/**
 * Batch operation that will create messages for the given scenario.
 */
function _apb_testing_volume_batch_messages($storage, &$context) {

  $limit           = 20; // Maximum number of chans processed
  $messagesPerChan = $storage['avg_msg_per_chan'];
  $totalChans      = $storage['user_count'] * $storage['per_user_chan_count'];
  $dayCount        = 10; // FIXME: Start soft

  if (empty($context['sandbox'])) {
    // We cannot create it all, the max value will be approximative.
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_chan'] = 0;
    $context['sandbox']['current_day'] = 0;
    // Afraid are you?
    $context['sandbox']['max'] = $totalChans * $dayCount;
  }

  $sandbox = &$context['sandbox'];

  $backend = apb_backend_get($storage['backend']);

  for ($i = 0; $i < $limit; ++$i) {

    // Compute channel id, we will just proceed to an infinite rotation on
    // the known channels until we reached the max value.
    $chanId = ((++$sandbox['progress']) % $totalChans);
    if ($chanId === 0) {
      $chanId = $limit;
    }

    $cursor = $backend->fetchChannels(array(Field::CHAN_ID => $chanId));
    foreach ($cursor as $chan) {
      // Send messages to the channel
      for ($j = 0; $j < $messagesPerChan; ++$j) {
        $chan->send($j);
      }
    }
  }

  if ($sandbox['max'] < $sandbox['progress']) {
    // Batch is finished
    $context['finished'] = 1;
  } else {
    if (0.99999 < ($context['finished'] = ($sandbox['progress'] / $sandbox['max']))) {
      $context['finished'] = 0.99999;
    }
  }
}
