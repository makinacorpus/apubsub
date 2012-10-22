<?php

/**
 * @file
 * APubSub administration pages
 */

/**
 * List all known backends
 */
function apb7_ui_content_backend_list() {
  return array('#markup' => "@todo : backend list");
}

/**
 * Display information about the given backend
 */
function apb7_ui_content_backend_overview_page($name) {
  $backend = apb7_backend_get($name);
  $build   = array();

  // Recap of backend capabilities, using some bit of introspection.
  $build['capabilities']['title'] = array(
    '#prefix' => '<h2>',
    '#suffix' => '</h2>',
    '#markup' => t("Capabilities"),
  );
  $yes  = t("Yes");
  $no   = t("No");
  $rows = array();

  $row = array(t("Backend can list channels:"));
  try {
    $backend->getChannelListHelper();
    $row[] = $yes;
  } catch (APubSub\Error\UncapableException $e) {
    $row[] = $no;
  }
  $rows[] = $row;

  $row = array(t("Backend can list subscriptions:"));
  try {
    $backend->getSubscriptionListHelper();
    $row[] = $yes;
  } catch (APubSub\Error\UncapableException $e) {
    $row[] = $no;
  }
  $rows[] = $row;

  $row = array(t("Backend can list subscribers:"));
  try {
    $backend->getSubscriberListHelper();
    $row[] = $yes;
  } catch (APubSub\Error\UncapableException $e) {
    $row[] = $no;
  }
  $rows[] = $row;

  $build['capabilities']['content']['#markup'] = theme('table', array('rows' => $rows));

  // Recap of backend options.
  $build['options']['title'] = array(
    '#prefix' => '<h2>',
    '#suffix' => '</h2>',
    '#markup' => t("Configuration"),
  );
  $rows = array();
  foreach ($backend->getContext()->getOptions() as $key => $value) {
    if (is_null($value)) {
      $value = '<em>null</em>';
    } else if (is_bool($value)) {
      $value = '<em>' . ($value ? "true" : "false") . '</em>';
    } else if (is_int($value)) {
      $value = $value; 
    } else if (is_string($value)) {
      $value = '"' . $value . '"';
    } else {
      $value = '<em>' . t("Undisplayable value type") . '</em>';
    }
    $rows[] = array($key, $value);
  }
  $build['options']['content']['#markup'] = theme('table', array('rows' => $rows));

  return $build;
}

/**
 * Display backend channel list
 */
function apb7_ui_content_backend_channel_list($name) {
  $backend = apb7_backend_get($name);
  $build = array();
  $limit = 16;

  $header = array(
    t("Id"),
    t("Created"),
    t("Operations"),
  );
  $rows = array();

  $list = $backend->getChannelListHelper();
  $list->setLimit($limit);

  pager_default_initialize($list->count(), $limit);
  if (isset($_GET['page'])) {
    $list->setOffset($limit * (int)$_GET['page']);
  }

  foreach ($list as $channel) {
    $rows[] = array(
      $channel->getId(),
      format_date($channel->getCreationTime()),
      '',
    );
  }

  $build['table']['#markup'] = theme('table', array(
    'header' => $header,
    'rows'   => $rows,
  ));

  $build['pager']['#markup'] = theme('pager');

  return $build;
}

/**
 * Display backend subscription list
 */
function apb7_ui_content_backend_subscription_list($name) {
  $backend = apb7_backend_get($name);
  $build = array();
  $limit = 16;
  $yes   = t("Yes");
  $no    = t("No");

  $header = array(
    t("Id"),
    t("Created"),
    t("(In)active since"),
    t("Channel"),
    t("Is active"),
    t("Operations"),
  );
  $rows = array();

  $list = $backend->getSubscriptionListHelper();
  $list->setLimit($limit);

  pager_default_initialize($list->count(), $limit);
  if (isset($_GET['page'])) {
    $list->setOffset($limit * (int)$_GET['page']);
  }

  foreach ($list as $subscription) {

    if ($active = $subscription->isActive()) {
      $since = $subscription->getStartTime();
    } else {
      $since = $subscription->getStopTime();
    }

    $rows[] = array(
      $subscription->getId(),
      format_date($subscription->getCreationTime()),
      format_date($since),
      check_plain($subscription->getChannelId()), // FIXME: Link?
      $active ? $yes : $no,
      '',
    );
  }

  $build['table']['#markup'] = theme('table', array(
    'header' => $header,
    'rows'   => $rows,
  ));

  $build['pager']['#markup'] = theme('pager');

  return $build;
}

/**
 * Display backend subscriber list
 */
function apb7_ui_content_backend_subscriber_list($name) {
  $backend = apb7_backend_get($name);
  $build = array();
  $limit = 16;
  $yes   = t("Yes");
  $no    = t("No");

  $header = array(
    t("Id"),
    t("Subscription count"),
    t("Active channels"),
    t("Operations"),
  );
  $rows = array();

  $list = $backend->getSubscriberListHelper();
  $list->setLimit($limit);

  pager_default_initialize($list->count(), $limit);
  if (isset($_GET['page'])) {
    $list->setOffset($limit * (int)$_GET['page']);
  }

  foreach ($list as $subscriber) {

    $count = 0;
    $names = array();
    foreach ($subscriber->getSubscriptions() as $subscription) {
      ++$count;
      if ($subscription->isActive()) {
        $names[] = $subscription->getChannelId();
      }
    }

    $rows[] = array(
      $subscriber->getId(),
      $count,
      implode(', ', $names),
      '',
    );
  }

  $build['table']['#markup'] = theme('table', array(
    'header' => $header,
    'rows'   => $rows,
  ));

  $build['pager']['#markup'] = theme('pager');

  return $build;
}
