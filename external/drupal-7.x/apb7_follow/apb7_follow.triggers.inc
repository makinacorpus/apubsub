<?php

/**
 * @file
 * Message send hooks for all user activity.
 */

use APubSub\Error\ChannelDoesNotExistException;

use Apb\Follow\Notification;

/**
 * Raise a message.
 *
 * API centric helper. Simple example of how sending a message with silent
 * fallback when the channel does not exists.
 *
 * @param object $id
 *   Who sent that.
 * @param string $type
 *   Channel type.
 * @param mixed $data
 *   Arbitrary mixed data that goes along.
 */
function apb7_follow_raise_message($id, $type, array $data = array()) {

  $chanId  = apb7_follow_user_key($id, $type);
  $backend = apb7_backend_get();

  try {
    $backend
      ->getChannel($chanId)
      ->send(array(
        // Those two are metadata used then by the Notification class
        '_i' => $id,
        '_t' => $type,
      ) + $data);

  } catch (ChannelDoesNotExistException $e) {
    // Nothing to do, no channel means no subscription!
  }
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

  apb7_follow_raise_message($id, APB_TYPE_ENTITY, $eventData);
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

  apb7_follow_raise_message($id, APB_TYPE_ENTITY, $eventData);
  apb7_follow_raise_message($user->uid, APB_TYPE_USER, $eventData);
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

  apb7_follow_raise_message($id, APB_TYPE_ENTITY, $eventData);
  apb7_follow_raise_message($user->uid, APB_TYPE_USER, $eventData);
}

/**
 * Implements hook_user_login().
 */
function apb7_follow_user_login(&$edit, $account) {
  apb7_follow_raise_message($account->uid, APB_TYPE_USER, array('a' => 'login'));
}

/**
 * Implements hook_user_logout().
 */
function apb7_follow_user_logout($account) {
  apb7_follow_raise_message($account->uid, APB_TYPE_USER, array('a' => 'logout'));
}
