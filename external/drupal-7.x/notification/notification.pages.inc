<?php

/**
 * @file
 * User pages.
 */

use APubSub\CursorInterface;
use APubSub\Error\SubscriptionDoesNotExistException;
use APubSub\Field;
use APubSub\Notification\NotificationService;
use APubSub\SubscriberInterface;
use APubSub\SubscriptionInterface;

/**
 * User notifications page.
 */
function notification_page_user_list($form, &$form_state, $account) {

  $service     = notification_service_get();
  $subscriber  = $service->getSubscriberFor(ACCOUNT_SUB, $account->uid);
  $displayed   = 0;
  $total       = 0;
  $limit       = variable_get(APB_VAR_USER_PAGER_LIMIT, APB_DEF_USER_PAGER_LIMIT);
  $element     = PagerDefault::$maxElement++;
  $offset      = pager_find_page($element) * $limit;
  $unmarkAll   = variable_get(APB_VAR_UNREAD_UNFOLD_ALL, APB_DEF_UNREAD_UNFOLD);
  $unmark      = $unmarkAll || variable_get(APB_VAR_UNREAD_UNFOLD, APB_DEF_UNREAD_UNFOLD);

  $cursor = $subscriber
    ->fetch()
    ->addSort(
        Field::MSG_SENT,
        CursorInterface::SORT_DESC)
    ->setRange($limit, $offset);

  $total = $cursor->getTotalCount();
  pager_default_initialize($total, $limit);

  $form['#tree']  = true;
  $form['#theme'] = 'notification_page';
  $form['list'] = array(
    '#type'    => 'checkboxes',
    '#options' => array(),
  );

  foreach ($cursor as $message) {
    $notification = $service->getNotification($message);

    ++$displayed;

    if ($uri = $notification->getImageUri()) {
      $image = notification_image_render($uri);
    } else {
      $image = '';
    }

    $form['list']['#options'][$message->getId()] = array(
      'image'  => $image,
      'time'   => $message->getSendTimestamp(),
      'unread' => $message->isUnread(),
      'text'   => $notification->format(),
      'type'   => $notification->getType(),
    );

    if (!$unmarkAll && $unmark && $message->isUnread()) {
      $message->setUnread(false);
    }
  }

  if ($unmarkAll) {
    $subscriber
      ->fetch()
      ->update(array(Field::MSG_UNREAD => 0));
  }

  $form['top']['#markup'] = format_plural(
    $total,
    "Displaying @displayed of @count notification",
    "Displaying @displayed of @count notifications",
    array('@displayed' => $displayed));

  $form['pager']['#markup'] = theme('pager');

  $form['actions'] = array(
    '#type'  => 'actions',
    'unread' => array(
      '#type'   => 'submit',
      '#value'  => t("Mark as unread"),
      '#submit' => array('notification_page_user_list_submit_unread'),
    ),
    'delete' => array(
      '#type'  => 'submit',
      '#value' => t("Delete"),
      '#submit' => array('notification_page_user_list_submit_delete'),
    ),
    'preferences' => array(
      '#type'  => 'link',
      '#title' => t("Preferences"),
      '#href'  => 'user/' . $account->uid . '/notifications/settings',
    ),
  );

  // If the user is submitting the form, new notifications could happen in
  // between, which would make the #options on form POST rebuild to change
  // compared to the previous form load on the initial GET, Drupal would
  // then detect this change as a form selection illegal choice and deliver
  // an error page to the user.
  // Since that the submit handler may only load notifications from the current
  // user subscriber, not matter how hard someone will try to hack the site he
  // will only be able to mark as read or unread its own notifications, so there
  // is no security issue in disabling the form validation from there.
  // For this to work, current user identifier cannot be set as a field value
  // but as a raw data in the $form structure, to avoid it transiting via the
  // form POST data.
  $form['#account'] = $account;
  $form['actions']['unread']['#limit_validation_errors'] = [];
  $form['actions']['delete']['#limit_validation_errors'] = [];

  return $form;
}

/**
 * User notifications page mark as unread submit handler.
 */
function notification_page_user_list_submit_unread($form, &$form_state) {

  $service    = notification_service_get();
  $account    = $form['#account'];
  $subscriber = $service->getSubscriberFor(ACCOUNT_SUB, $account->uid);
  $count      = 0;

  // Because we disabled form validation, we need to be able to fetch the
  // identifiers from the POST array instead of the form values, hopefully
  // form API carries it in the form state.
  $msgIdList = array_map('intval', array_keys(array_filter($form_state['input']['list'])));
  $count = count($msgIdList);

  if ($msgIdList) {
    $subscriber
      ->fetch(array(
          Field::MSG_ID => $msgIdList,
      ))
      ->update([
          Field::MSG_UNREAD => 1,
      ])
    ;
  }

  drupal_set_message(format_plural($count,
    "@count message marked as unread",
    "@count messages marked as unread"));
}

/**
 * User notifications page delete submit handler.
 */
function notification_page_user_list_submit_delete($form, &$form_state) {

  $service    = notification_service_get();
  $account    = $form['#account'];
  $subscriber = $service->getSubscriberFor(ACCOUNT_SUB, $account->uid);
  $count      = 0;

  // Because we disabled form validation, we need to be able to fetch the
  // identifiers from the POST array instead of the form values, hopefully
  // form API carries it in the form state.
  $msgIdList = array_map('intval', array_keys(array_filter($form_state['input']['list'])));
  $count = count($msgIdList);

  if ($msgIdList) {
    $subscriber
      ->fetch(array(
        Field::MSG_ID => $msgIdList,
      ))
      ->delete()
    ;
  }

  drupal_set_message(format_plural($count,
    "@count message deleted",
    "@count messages deleted"));
}

/**
 * User notifications subscriptions page.
 */
function notification_page_user_subscriptions($form, &$form_state, $account) {

  $service          = notification_service_get();
  $chanTypeRegistry = $service->getChanTypeRegistry();
  $subscriber       = $service->getSubscriberFor(ACCOUNT_SUB, $account->uid);

  $options = array();
  foreach ($subscriber->getSubscriptions() as $subscription) {
    if ($subscription instanceof SubscriptionInterface) {

      $chan        = $subscription->getChannel();
      $chan_name   = $chan->getId();
      $id          = null;
      $chanType = null;
      $created     = $subscription->getCreationTime();

      if (FALSE !== strpos($chan_name, ':')) {
        list($type, $id)   = explode(':', $chan_name, 2);
        $chanType = $chanTypeRegistry->getInstance($type);
      } else {
        $chanType = $chanTypeRegistry->getInstance(null);
      }

      if ($chanType->isVisible()) {
        $options[$chan_name] = array(
          'desc'   => $chanType->getSubscriptionLabel($id),
          'since'  => format_date($created) . " / " . format_interval(time() - $created),
          'status' => $subscription->isActive() ? t("Enabled") : t("Disabled"),
        );
      }
    }
  }

  $form['uid'] = array(
    '#type'  => 'value',
    '#value' => $account->uid,
  );

  $form['channels'] = array(
    '#type'    => 'tableselect',
    '#header'  => array(
      'desc'   => t("Name"),
      'since'  => t("Exists since"),
      'status' => t("Stats"),
    ),
    '#options' => $options,
    '#empty' => t('There is no subscription.'),
  );

  if ($options) {

    $form['actions'] = array(
      '#type'     => 'actions',
    );
    $form['actions']['enable'] = array(
      '#type'     => 'submit',
      '#value'    => t("Enable"),
      '#submit'   => array('notification_page_user_subscriptions_submit_enable'),
      '#disabled' => empty($options),
    );
    $form['actions']['disable'] = array(
      '#type'     => 'submit',
      '#value'    => t("Disable"),
      '#submit'   => array('notification_page_user_subscriptions_submit_disable'),
      '#disabled' => empty($options),
    );
    $form['actions']['delete'] = array(
      '#type'     => 'submit',
      '#value'    => t("Unfollow"),
      '#submit'   => array('notification_page_user_subscriptions_submit_delete'),
      '#disabled' => empty($options),
    );
  }

  return $form;
}

/**
 * User notifications preference page enable submit handler.
 */
function notification_page_user_subscriptions_submit_enable($form, &$form_state) {

  $service    = notification_service_get();
  $uid        = $form_state['values']['uid'];
  $subscriber = $service->getSubscriberFor(ACCOUNT_SUB, $uid);

  $selected   = array();

  foreach ($form_state['values']['channels'] as $chan_id => $enabled) {
    if ($chan_id === $enabled) {
      $selected[] = $enabled;
    }
  }

  if (empty($selected)) {
    drupal_set_message(t("Nothing to do."));
    return;
  }

  foreach ($selected as $chan_id) {
    try {
      $subscriber->getSubscriptionFor($chan_id)->activate();
    } catch (SubscriptionDoesNotExistException $e) {
      // Nothing to do here. Channel may have been suppressed by another thread.
      // Life is life and everything goes on.
    }
  }

  drupal_set_message(t("Subscriptions where enabled successfully."));
}

/**
 * User notifications preference page disable submit handler.
 */
function notification_page_user_subscriptions_submit_disable($form, &$form_state) {

  $service    = notification_service_get();
  $uid        = $form_state['values']['uid'];
  $subscriber = $service->getSubscriberFor(ACCOUNT_SUB, $uid);

  $selected   = array();

  foreach ($form_state['values']['channels'] as $chan_id => $enabled) {
    if ($chan_id === $enabled) {
      $selected[] = $enabled;
    }
  }

  if (empty($selected)) {
    drupal_set_message(t("Nothing to do."));
    return;
  }

  foreach ($selected as $chan_id) {
    try {
      $subscriber->getSubscriptionFor($chan_id)->deactivate();
    } catch (SubscriptionDoesNotExistException $e) {
      // Nothing to do here. Channel may have been suppressed by another thread.
      // Life is life and everything goes on.
    }
  }

  drupal_set_message(t("Subscriptions where disabled successfully."));
}

/**
 * User notifications preference page delete submit handler.
 */
function notification_page_user_subscriptions_submit_delete($form, &$form_state) {

  $service    = notification_service_get();
  $uid        = $form_state['values']['uid'];
  $subscriber = $service->getSubscriberFor(ACCOUNT_SUB, $uid);

  $selected   = array();

  foreach ($form_state['values']['channels'] as $chan_id => $enabled) {
    if ($chan_id === $enabled) {
      $selected[] = $enabled;
    }
  }

  if (empty($selected)) {
    drupal_set_message(t("Nothing to do."));
    return;
  }

  foreach ($selected as $chan_id) {
    $subscriber->unsubscribe($chan_id);
  }

  drupal_set_message(t("Subscriptions where unfollowed successfully."));
}

/**
 * User settings for subscriptions
 */
function notification_page_user_settings($form, &$form_state, $account) {

  $form['uid'] = array(
    '#type'  => 'value',
    '#value' => $account->uid,
  );

  $form['actions'] = array(
    '#type'   => 'actions',
  );
  $form['actions']['save'] = array(
    '#type'   => 'submit',
    '#value'  => t("Save"),
  );

  return $form;
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_notification_page(&$variables) {

  $form = &$variables['form'];

  foreach (element_children($form['list']) as $key) {
    // Should have already been expanded.
    // Sad checkbox is sad.
    $item = &$form['list'][$key]['#title'];
    unset($form['list'][$key]['#title']);

    $item['checkbox'] = drupal_render($form['list'][$key]);

    $variables['list'][] = $item;
  }

  $variables['top']   = drupal_render($form['top']);
  $variables['pager'] = drupal_render($form['pager']);
  $variables['form']  = drupal_render_children($form);
}
