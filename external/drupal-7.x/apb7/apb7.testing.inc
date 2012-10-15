<?php

/**
 * @file
 * APubSub testing pages.
 */

/**
 * Channels page listing page.
 */
function apb7_testing_channels_form($form, &$form_state) {
  $backend = apb7_backend_get();

  $limit = 10;
  if (isset($_GET['page'])) {
    $offset = ((int)$_GET['page']) * $limit;
  } else {
    $offset = 0;
  }

  pager_default_initialize(10000, $limit);

  $options = array();
  foreach ($backend->listChannels($limit, $offset) as $channel) {
    $options[$channel->getId()] = array(
      'id' => $channel->getId(),
    );
  }

  $form['channels'] = array(
    '#type' => 'tableselect',
    '#options' => $options,
    '#header' => array(
      'id' => t("Identifier"),
    ),
  );
  $form['pager']['#markup'] = theme('pager');

  return $form;
}
