<?php

/**
 * @file
 * Message send hooks for all user activity.
 */

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

  apb7_notification_manager_get()->notify(APB_TYPE_ENTITY, $id, $eventData);
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

  $manager = apb7_notification_manager_get();
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

  $manager = apb7_notification_manager_get();
  $manager->notify(APB_TYPE_ENTITY, $id, $eventData);
  $manager->notify(APB_TYPE_USER, $user->uid, $eventData);
}

/**
 * Implements hook_user_login().
 */
function apb7_follow_user_login(&$edit, $account) {
  apb7_notification_manager_get()->notify(APB_TYPE_USER, $account->uid, array('a' => 'login'));
}

/**
 * Implements hook_user_logout().
 */
function apb7_follow_user_logout($account) {
  apb7_notification_manager_get()->notify(APB_TYPE_USER, $account->uid, array('a' => 'logout'));
}
