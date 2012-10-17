<?php

/**
 * @file
 * APubSub stress test.
 */

/**
 * Massive stress test entry point.
 */
function apb7_testing_volume_form($form, &$form_state) {
  $form['#tree'] = TRUE;

  if (!isset($form_state['storage'])) {
    $form_state['storage'] = array();
  }

  $storage = &$form_state['storage'];

  // Set default values for everything that will be used during this import
  // Consider that we have a use case for this scenario: a social network
  // notification system.
  $storage += array(
    // User driven variable.
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
    'avg_modifier'        => 0.15,   // Average more or less 15%.
  );

  // Empty() because fucking database may loose typing at some point and
  // because in PHP: "any string" == 0.
  if (empty($storage['current'])) {
    // Start
    $form = apb7_testing_volume_form_step_start($form, $form_state, $storage);
    $submit = 'apb7_testing_volume_form_step_start_submit';
  } else if ($storage['day_count'] <= $storage['current']) {
    // End
    $form = apb7_testing_volume_form_step_synthesis($form, $form_state, $storage);
    $submit = 'apb7_testing_volume_form_step_synthesis_submit';
  } else {
    // Middle
    $form = apb7_testing_volume_form_step_step($form, $form_state, $storage);
    $submit = 'apb7_testing_volume_form_step_step_submit';
  }

  $form['actions'] = array(
    '#type' => 'actions',
    'submit' => array(
      '#type' => 'submit',
      '#value' => t("Go!"),
      '#submit' => array($submit),
    ),
  );

  return $form;
}

function apb7_testing_volume_form_step_start($form, &$form_state, &$storage) {
  $form['help'] = array(
    '#prefix' => '<p>',
    '#suffix' => '</p>',
    '#markup' => t("We are going to run a <em>social network simulation scenario</em>." .
       " Each day passing will generate a certain amount of notifications, in a certain" .
       " amount of distribution channels, thus stressing the system a bit."),
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

  return $form;
}

function apb7_testing_volume_form_step_start_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
  $storage = &$form_state['storage'];

  // Mark the multistep for beginning.
  $storage['current'] = 1;

  // Reset values.
  foreach ($form_state['values']['data'] as $key => $value) {
    if (array_key_exists($key, $storage)) {
      $storage[$key] = $value;
    }
  }

  // Go for user and channels creation.
  batch_set(array(
    'operations' => array(
      array('_apb7_testing_volume_batch_setup', array($storage)),
      array('_apb7_testing_volume_stepping', array($storage)),
    ),
    'title' => 'Creating channels and subscriptions',
    'finished' => 'update_finished',
    'file' => drupal_get_path('module', 'apb7') . '/apb7.testing-volume.inc',
  ));
}

/*
 * FIXME: Need interface for this, we will do manual cleanup waiting for this.
 *
function _apb7_testing_volume_batch_cleanup(&$context) {
}
 */

function _apb7_testing_volume_batch_setup($storage, &$context) {

  if (empty($context['sandbox'])) {
    // We cannot create it all, the max value will be approximative.
    $context['sandbox']['progress']    = 0;
    $context['sandbox']['current']     = 0;
    $context['sandbox']['current_sub'] = 0;
    $context['sandbox']['current_max'] = 0;
    // Afraid are you?
    $context['sandbox']['max'] = $storage['user_count'] * $storage['per_user_chan_count'];
  }

  $sandbox = &$context['sandbox'];

  // N SQL requests per run
  $limit = 100;
  $count = 0;

  $backend = apb7_backend_get();

  while ($count < $limit || $sandbox['max'] < $sandbox['progress']) {
    // Continue last channel if not finished
    if ($sandbox['current_chan'] && ($sandbox['current_sub'] < $sandbox['current_max'])) {
      $channel = $backend->getChannel($sandbox['current_chan']);
    } else {
      ++$sandbox['progress'];
      $channel = $backend->createChannel($sandbox['progress']);
      $sandbox['current_max'] = ceil($storage['avg_user_sub_count'] * $storage['per_user_chan_count'] * $storage['avg_user_chan_count']);
      $sandbox['current_sub'] = 0;
    }

    for ($i = 0; $i < $sandbox['current_max']; ++$i) {
      // Hopefully, default database backend will use a correct sequence
      $channel->subscribe();
      ++$sandbox['current_sub'];

      if ($limit < (++$count)) {
        break;
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

/*  $storage += array(
    // User driven variable.
    'user_count'          => 100000, // Number of users of this network.
    'per_user_chan_count' => 2,      // Each user is a certain number of channel
                                     // (for exemple, private and public).
    'avg_msg_per_chan'    => 10,     // Average of 10 messages per chan per day.
    'avg_user_sub_count'  => 15,     // Each user has 15 friends.
    'avg_user_chan_count' => 0.5 ,   // Each user subscribes to a 50% average of
                                     // its friends channels (public or
                                     // private).
    'sub_fetch_rate'      => 0.3,    // A subscriber connects 1 day out of 3.
    'day_count'           => 365,    // One year stress test per default.
    'stepping'            => 13,     // Each iteration is 13 days.
    'avg_modifier'        => 0.15,   // Average more or less 15%.
    // Working variables.
    'current'             => 0,      // Start at day 0.
    'time'                => 0,      // Time taken in computations, in seconds.
    'step_count'          => 0,      // Number of different steps the user did.
  ); */

/**
 * Iteration.
 */
function apb7_testing_volume_form_step_synthesis($form, &$form_state, &$storage) {

  $rows = array();
  $row[] = array();

  $form['actions'] = array(
    '#type' => 'actions',
    'continue' => array(
      '#type' => 'submit',
      '#value' => t("Continue"),
    ),
  );

  return $form;
}

function apb7_testing_volume_form_step_step($form, &$form_state, &$storage) {
  // @todo
}
