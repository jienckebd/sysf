<?php

/**
 * @file
 * Builds placeholder replacement tokens for workspaces.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function design_system_token_info() {

  $types = $tokens = [];

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function design_system_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if (!empty($data['entity'])) {
    foreach ($tokens as $name => $original) {

      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $data['entity'];

      switch ($name) {

        case 'entity-id':
          $replacements[$original] = $entity->id();
          break;

        case 'entity-type-id':
          $replacements[$original] = $entity->getEntityTypeId();
          break;

      }

    }
  }

  if (!in_array($type, ['current-page'])) {
    return $replacements;
  }

  $static_cache = &drupal_static(__METHOD__, []);
  if (empty($static_cache['entity_from_route'])) {
    $static_cache['entity_from_route'] = \Drupal::service('design.system')->getEntityFromRoute();
    if (empty($static_cache['entity_from_route'])) {
      return $replacements;
    }
  }

  $mocked_tokens = [];
  $map_mocked_token = [];

  $entity_type_id = $static_cache['entity_from_route']->getEntityTypeId();

  foreach ($tokens as $name => $original) {

    if (stripos($name, 'entity:') === 0) {
      $new_token = substr($name, 7);
      $new_original = "[{$entity_type_id}:{$new_token}]";
      $mocked_tokens[$new_token] = $new_original;
      $map_mocked_token[$new_token] = $original;
    }

  }

  if (empty($mocked_tokens)) {
    return $replacements;
  }

  $token_service = \Drupal::token();
  $mocked_data[$entity_type_id] = $static_cache['entity_from_route'];

  foreach ($mocked_tokens as $name => $original) {
    if ($replaced_value = $token_service->replace($original, $mocked_data, ['clear' => TRUE])) {

      $original_original_token_id = $map_mocked_token[$name];

      $replacements[$original_original_token_id] = $replaced_value;
    }
  }

  return $replacements;
}
