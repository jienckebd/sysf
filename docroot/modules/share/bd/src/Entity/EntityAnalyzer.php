<?php

namespace Drupal\bd\Entity;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class EntityAnalyzer.
 */
class EntityAnalyzer {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * Analyzer constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   */
  public function __construct(EntityHelper $entity_helper) {
    $this->entityHelper = $entity_helper;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $variables
   *
   * @return array
   */
  public function getEntityMetaData(EntityInterface $entity, $variables) {
    $entity_type = $entity->getEntityType();

    $metadata = [
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ];

    if ($entity_type->isTranslatable()) {
      $metadata['langcode'] = $entity->language()->getId();
    }

    if ($entity_type->getKey('bundle')) {
      $metadata['bundle_id'] = $entity->bundle();
    }

    if (!empty($variables['elements']['#view_mode'])) {
      $metadata['view_mode'] = $variables['elements']['#view_mode'];
    }

    // Use components above this to generate unique ID.
    $dom_id = "entity";
    foreach ($metadata as $key => $value) {
      $dom_id .= "--{$value}";
    }
    $metadata['unique_id'] = $dom_id;

    // Keys below don't go in unique ID.
    if ($entity_type->isRevisionable()) {
      $metadata['revision_id'] = $entity->getRevisionId();
    }

    if ($published_key = $entity_type->getKey('published')) {
      $metadata['is_published'] = (int) $entity->get($published_key)->value;
    }

    if ($entity->hasField('promote')) {
      $metadata['is_promote'] = (int) $entity->promote->value;
    }

    if ($entity->hasField('sticky')) {
      $metadata['is_sticky'] = (int) $entity->sticky->value;
    }

    $metadata['uuid'] = $entity->uuid();

    return $metadata;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $match_entity_type_ids
   * @param $match_bundle_ids
   * @param null $max_recursion
   * @param int $current_recursion
   *
   * @return array
   */
  public function getReferencedEntity(EntityInterface $entity, $match_entity_type_ids, $match_bundle_ids, $max_recursion = NULL, $current_recursion = 0) {

    $current_recursion++;

    $return = [];

    if (!empty($max_recursion) && ($current_recursion >= $max_recursion)) {
      return $return;
    }

    foreach ($entity->referencedEntities() as $entity_id => $entity_referenced) {

      if (!empty($match_entity_type_ids) && !in_array($entity_referenced->getEntityTypeId(), $match_entity_type_ids)) {
        continue;
      }

      if (!empty($match_bundle_ids) && !in_array($entity_referenced->bundle(), $match_bundle_ids)) {
        continue;
      }

      $return[$entity_referenced->id()] = $entity_referenced;
      if ($entity_referenced_return = $this->getReferencedEntity($entity_referenced, $match_entity_type_ids, $match_bundle_ids, $max_recursion, $current_recursion)) {
        $return = NestedArray::mergeDeep($return, $entity_referenced_return);
      }

    }

    return $return;
  }

}
