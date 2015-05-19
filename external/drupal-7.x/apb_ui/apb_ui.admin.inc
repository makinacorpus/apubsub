<?php

/**
 * @file
 * APubSub administration pages
 */

use APubSub\BackendInterface;

/**
 * List all known backends
 */
function apb_ui_content_backend_list() {

  $headers = array(t("Name"), t("Class"), t("Status"), t("Operations"));
  $rows = array();

  foreach (apb_backend_list() as $name => $backend) {
    $row = array();

    $row[] = check_plain($name);
    $row[] = check_plain($backend['class']);
    $row[] = class_exists($backend['class']) ? t("Active") : ('<strong>' . t("Class does not exist!") . '</strong>');

    $links = array(
      array(
        'title' => t("Overview"),
        'href' => 'admin/content/pubsub/' . $name,
      ),
    );

    $row[] = theme('links', array('links' => $links));

    $rows[] = $row;
  }

  return theme('table', array('header' => $headers, 'rows' => $rows));
}

/**
 * Display information about the given backend
 */
function apb_ui_content_backend_overview_page(BackendInterface $backend) {
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
  $backend->getChannelListHelper();
  $row[] = $yes;
  $rows[] = $row;

  $row = array(t("Backend can list subscriptions:"));
  $backend->getSubscriptionListHelper();
  $row[] = $yes;
  $rows[] = $row;

  $row = array(t("Backend can list subscribers:"));
  $backend->getSubscriberListHelper();
  $row[] = $yes;
  $rows[] = $row;

  $build['capabilities']['content']['#markup'] = theme('table', array('rows' => $rows));

  // Recap of backend options.
  $build['options']['title'] = array(
    '#prefix' => '<h2>',
    '#suffix' => '</h2>',
    '#markup' => t("Configuration"),
  );

  return $build;
}

/**
 * Run backend analysis and display result.
 */
function apb_ui_content_backend_analysis(BackendInterface $backend) {

  if (!$analysis = $backend->getAnalysis()) {
    return array(
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => t("This backend does not support analysis feature."),
    );
  }

  $rows = array();

  foreach ($analysis as $name => $value) {
    if (is_null($value)) {
      $value = '<em>null</em>';
    } else if (is_bool($value)) {
      $value = '<em>' . ($value ? "true" : "false") . '</em>';
    } else if (is_int($value)) {
      $value = number_format($value); 
    } else if (is_string($value)) {
      $value = '"' . $value . '"';
    } else {
      $value = '<em>' . t("Undisplayable value type") . '</em>';
    }

    $name = t($name);

    $rows[] = array($name, $value);
  }

  return theme('table', array('rows' => $rows));
}

/**
 * Display backend channel list
 */
function apb_ui_content_backend_channel_list(BackendInterface $backend) {
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

  /* @var $list APubSub\ChannelInterface */
  foreach ($list as $chan) {
    $rows[] = array(
      $chan->getId(),
      format_date($chan->getCreationDate()->getTimestamp()),
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
function apb_ui_content_backend_subscription_list(BackendInterface $backend) {
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

  /* @var $list APubSub\SubscriptionInterface */
  foreach ($list as $subscription) {

    if ($active = $subscription->isActive()) {
      $since = $subscription->getStartDate()->getTimestamp();
    } else {
      $since = $subscription->getStopDate()->getTimestamp();
    }

    $rows[] = array(
      $subscription->getId(),
      format_date($subscription->getCreationDate()->getTimestamp()),
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
function apb_ui_content_backend_subscriber_list(BackendInterface $backend) {
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
