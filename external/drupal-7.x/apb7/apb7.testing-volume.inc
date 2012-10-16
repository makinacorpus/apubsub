<?php

/**
 * @file
 * APubSub stress test.
 */

/**
 * Massive stress test entry point.
 */
function apb7_testing_volume_form($form, &$form_state) {

  if (!isset($form_state['storage'])) {
    $form_state['storage'] = array();
  }

  $storage = &$form_state['storage'];

  // Set default values for everything that will be used during this import
  // Consider that we have a use case for this scenario: a social network
  // notification system.
  $storage += array(
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
  );

  // Empty() because fucking database may loose typing at some point and
  // because in PHP: "any string" == 0.
  if (empty($storage['current'])) {
    // Start
    return apb7_testing_volume_form_step_start($form, $form_state, $storage);
  } else if ($storage['day_count'] <= $storage['current']) {
    // End
    return apb7_testing_volume_form_step_synthesis($form, $form_state, $storage);
  } else {
    // Middle
    return apb7_testing_volume_form_step_step($form, $form_state, $storage);
  }
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
    '#title' => '',
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
    '#description' => '',
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
  $form['data']['stepping'] = array(
    '#type' => 'select',
    '#title' => t('Stepping'),
    '#options' => array(
      1   => 1,
      3   => 3,
      5   => 5,
      8   => 8,
      13  => 13,
      21  => 21,
      34  => 34,
      55  => 55,
      89  => 89,
      144 => 144,
    ),
    '#default_value' => $storage['stepping'],
    '#required' => TRUE,
    '#description' => t("Number of days spent per iteration."),
  );

  return $form;
}

function apb7_testing_volume_form_step_synthesis($form, &$form_state, &$storage) {
  // @todo
}

function apb7_testing_volume_form_step_step($form, &$form_state, &$storage) {
  // @todo
}
