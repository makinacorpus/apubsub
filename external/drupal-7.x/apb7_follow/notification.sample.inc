<?php

/**
 * @file
 * Message send hooks for all user activity.
 */

/**
 * Notification type for user following.
 */
const APB_TYPE_USER = 'u';

/**
 * Notification type for entity following.
 */
const APB_TYPE_ENTITY = 'e';

/**
 * Implements hook_notification_type_info().
 */
function apb7_follow_notification_type_info() {
  return array(
    APB_TYPE_ENTITY => array(
      'class'       => 'Apb\Notification\Formatter\EntityFormatter',
      'description' => t("Entities (Example)"),
    ),
    APB_TYPE_USER   => array(
      'class'       => 'Apb\Notification\Formatter\UserActionFormatter',
      'description' => t("User (Example)"),
    ),
  );
}

/**
 * Implements hook_notification_channel_info().
 */
function apb7_follow_notification_channel_info() {
  return array(
    APB_TYPE_ENTITY => array(
      'description' => t("Entity channel"),
      'visible'     => true,
    ),
  );
}

/**
 * Tell if the entity type follow operation is supported.
 *
 * @param string $type
 *   Entity type.
 *
 * @return bool
 *   True if supported.
 */
function apb7_follow_entity_type_is_supported($type) {
  return 'node' === $type;
}

/**
 * Implements hook_entity_insert().
 */
function apb7_follow_entity_insert($entity, $entityType) {
  global $user;

  if (!apb7_follow_entity_type_is_supported($entityType)) {
    return;
  }

  list($id, $vid, $bundle) = entity_extract_ids($entityType, $entity);

  if (($uri = entity_uri($entityType, $entity)) && isset($uri['path'])) {
    $uri = $uri['path'];
  } else {
    $uri = null;
  }

  $eventData = array(
    'a'    => 'insert',
    'uid'  => $user->uid,
    'id'   => $id,
    'vid'  => $vid,
    'type' => $entityType,
  );

  notification_manager_get()->notify(APB_TYPE_ENTITY, $id, $eventData);
}

/**
 * Implements hook_entity_update().
 */
function apb7_follow_entity_update($entity, $entityType) {
  global $user;

  if (!apb7_follow_entity_type_is_supported($entityType)) {
    return;
  }

  list($id, $vid, $bundle) = entity_extract_ids($entityType, $entity);

  if (($uri = entity_uri($entityType, $entity)) && isset($uri['path'])) {
    $uri = $uri['path'];
  } else {
    $uri = null;
  }

  $eventData = array(
    'a'    => 'update',
    'uid'  => $user->uid,
    'id'   => $id,
    'vid'  => $vid,
    'type' => $entityType,
  );

  $manager = notification_manager_get();
  $manager->notify(APB_TYPE_ENTITY, $id, $eventData);
  $manager->notify(APB_TYPE_USER, $user->uid, $eventData);
}

/**
 * Implements hook_entity_delete().
 */
function apb7_follow_entity_delete($entity, $entityType) {
  global $user;

  if (!apb7_follow_entity_type_is_supported($entityType)) {
    return;
  }

  list($id, $vid, $bundle) = entity_extract_ids($entityType, $entity);

  $eventData = array(
    'a'    => 'delete',
    'uid'  => $user->uid,
    'id'   => $id,
    'vid'  => $vid,
    'type' => $entityType,
  );

  $manager = notification_manager_get();
  $manager->notify(APB_TYPE_ENTITY, $id, $eventData);
  $manager->notify(APB_TYPE_USER, $user->uid, $eventData);
}

/**
 * Implements hook_user_login().
 */
function apb7_follow_user_login(&$edit, $account) {
  notification_manager_get()->notify(APB_TYPE_USER, $account->uid, array('a' => 'login'));
}

/**
 * Implements hook_user_logout().
 */
function apb7_follow_user_logout($account) {
  notification_manager_get()->notify(APB_TYPE_USER, $account->uid, array('a' => 'logout'));
}
