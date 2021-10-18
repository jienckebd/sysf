<?php

namespace Drupal\bd\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class EntityRelation.
 */
class EntityRelation {

  /**
   * The entity type ID used for dependencies.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_RELATION = 'relation';

  /**
   * Defines the bundle ID of dependencies.
   *
   * @var string
   */
  const BUNDLE_ID_DEPENDENCY = 'dependency';

  /**
   * UUID for dependency type.
   *
   * @var string
   */
  const DEPENDENCY_TYPE_DELETE_REFERENCED = 76;

  /**
   * UUID for dependency type.
   *
   * @var string
   */
  const DEPENDENCY_TYPE_RESYNC_REFERENCED = 77;

  /**
   * UUID for dependency type.
   *
   * @var string
   */
  const DEPENDENCY_TYPE_RESAVE_REFERENCED = 114;

  /**
   * UUID for dependency type.
   *
   * @var string
   */
  const DEPENDENCY_TYPE_DELETE_REFERENCING = 78;

  /**
   * UUID for dependency type.
   *
   * @var string
   */
  const DEPENDENCY_TYPE_RESYNC_REFERENCING = 79;

  /**
   * UUID for dependency type.
   *
   * @var string
   */
  const DEPENDENCY_TYPE_RESAVE_REFERENCING = 113;

  /**
   * Field types to support dependencies.
   *
   * @var array
   */
  const FIELD_TYPE_DEPENDENCY = [
    'entity_reference',
    'entity_reference_revisions',
    'dynamic_entity_reference',
  ];

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * The entity builder.
   *
   * @var \Drupal\bd\Entity\EntityBuilder
   * */
  public $entityBuilder;

  /**
   * The entity analyzer.
   *
   * @var \Drupal\bd\Entity\EntityAnalyzer
   */
  public $entityAnalyzer;

  /**
   * Relation constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\bd\Entity\EntityBuilder $entity_builder
   * @param \Drupal\bd\Entity\EntityAnalyzer $entity_analyzer
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityBuilder $entity_builder,
    EntityAnalyzer $entity_analyzer
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityBuilder = $entity_builder;
    $this->entityAnalyzer = $entity_analyzer;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $related_entity_type_id
   * @param null $related_bundle_id
   * @param int $max_depth
   * @param int $current_depth
   * @param array $related_entity_id_list
   *
   * @return array
   */
  public function getReferencedByBundle(EntityInterface $entity, $related_entity_type_id, $related_bundle_id = NULL, $max_depth = 1, $current_depth = 1, array &$related_entity_id_list = []) {
    foreach ($entity->referencedEntities() as $referenced_entity) {

      if ($referenced_entity->getEntityTypeId() == $related_entity_type_id) {
        if (!empty($related_bundle_id)) {
          if ($referenced_entity->bundle() == $related_bundle_id) {
            $related_entity_id_list[] = $referenced_entity->id();
          }
        }
        else {
          $related_entity_id_list[] = $referenced_entity->id();
        }
      }

      if ($current_depth <= $max_depth) {
        $current_depth++;
        $this->getReferencedByBundle($entity, $related_entity_type_id, $related_bundle_id, $max_depth, $current_depth, $related_entity_id_list);
      }

    }

    return $related_entity_id_list;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $match_entity_type_ids
   * @param array $match_bundle_ids
   * @param int|string $target_entity_type_id
   * @param null $max_recursion
   */
  public function buildDependencyReferencedEntities(EntityInterface $entity, $match_entity_type_ids = [], $match_bundle_ids = [], $dependency_type = self::DEPENDENCY_TYPE_RESAVE_REFERENCING, $max_recursion = NULL) {

    $entity_values = [
      'field_type' => $dependency_type,
    ];

    $this->buildRelationReferencedEntities($entity, $match_entity_type_ids, $match_bundle_ids, 'dependency', $entity_values, $max_recursion);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $match_entity_type_ids
   * @param array $match_bundle_ids
   * @param string $relation_type
   * @param array $entity_values
   * @param null $max_recursion
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildRelationReferencedEntities(EntityInterface $entity, $match_entity_type_ids = [], $match_bundle_ids = [], $relation_type = 'dependency', $entity_values = [], $max_recursion = NULL) {
    if ($referenced_entities = $this->entityAnalyzer->getReferencedEntity($entity, $match_entity_type_ids, $match_bundle_ids)) {
      foreach ($referenced_entities as $referenced_entity) {
        $this->buildRelation($entity->getEntityTypeId(), $entity->id(), $referenced_entity->getEntityTypeId(), $referenced_entity->id(), $relation_type, $entity_values);
      }
    }
    return $referenced_entities;
  }

  /**
   * @param $source_entity_type_id
   * @param $source_entity_id
   * @param $target_entity_type_id
   * @param $target_entity_id
   * @param $relation_type
   * @param array $entity_values
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildRelation($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type, array $entity_values = []) {
    if (!$this->hasRelation($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type, $entity_values)) {
      $this->saveRelation($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type, $entity_values);
    }
  }

  /**
   * @param $source_entity_type_id
   * @param $source_entity_id
   * @param $target_entity_type_id
   * @param $target_entity_id
   * @param string $relation_type
   * @param array $entity_values
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveRelation($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type = self::BUNDLE_ID_DEPENDENCY, array $entity_values = []) {
    $entity_storage = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_RELATION);
    $entity_values = $this->getEntityValues($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type, $entity_values);
    $entity = $entity_storage->create($entity_values);
    $entity->save();
  }

  /**
   * @param $source_entity_type_id
   * @param $source_entity_id
   * @param $target_entity_type_id
   * @param $target_entity_id
   * @param string $relation_type
   * @param array $entity_values
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function hasRelation($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type = self::BUNDLE_ID_DEPENDENCY, $entity_values = []) {
    $entity_storage = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_RELATION);
    $entity_values = $this->getEntityValues($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type, $entity_values);
    return (bool) $entity_storage->loadByProperties($entity_values);
  }

  /**
   * @param $source_entity_type_id
   * @param $source_entity_id
   * @param $target_entity_type_id
   * @param $target_entity_id
   * @param string $relation_type
   * @param array $entity_values
   *
   * @return array
   */
  public function getEntityValues($source_entity_type_id, $source_entity_id, $target_entity_type_id, $target_entity_id, $relation_type = self::BUNDLE_ID_DEPENDENCY, array $entity_values = []) {
    if (empty($entity_values['bundle'])) {
      $entity_values['bundle'] = $relation_type;
    }
    $entity_values['source'] = [
      'target_type' => $source_entity_type_id,
      'target_id' => $source_entity_id,
    ];
    $entity_values['target'] = [
      'target_type' => $target_entity_type_id,
      'target_id' => $target_entity_id,
    ];
    return $entity_values;
  }

  /**
   * @param $target_entity_type_id
   * @param $target_entity_id
   * @param null $relation_type
   * @param array $entity_values
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelationsSource($source_entity_type_id, $source_entity_id, $relation_type = NULL, array $entity_values = []) {

    $entity_values['source'] = [
      'target_type' => $source_entity_type_id,
      'target_id' => $source_entity_id,
    ];

    if (!empty($relation_type)) {
      $entity_values['bundle'] = $relation_type;
    }

    return $this->loadDependency($entity_values, 'target');
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param null $relation_type
   * @param array $entity_values
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelationsSourceByEntity(EntityInterface $entity, $relation_type = NULL, array $entity_values = []) {
    return $this->getRelationsSource($entity->getEntityTypeId(), $entity->id(), $relation_type, $entity_values);
  }

  /**
   * @param $target_entity_type_id
   * @param $target_entity_id
   * @param null $relation_type
   * @param array $entity_values
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelationsTarget($target_entity_type_id, $target_entity_id, $relation_type = NULL, array $entity_values = []) {

    $entity_values['target'] = [
      'target_type' => $target_entity_type_id,
      'target_id' => $target_entity_id,
    ];

    if (!empty($relation_type)) {
      $entity_values['bundle'] = $relation_type;
    }

    return $this->loadDependency($entity_values, 'source');
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param null $relation_type
   * @param array $entity_values
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelationsTargetByEntity(EntityInterface $entity, $relation_type = NULL, array $entity_values = []) {
    return $this->getRelationsTarget($entity->getEntityTypeId(), $entity->id(), $relation_type, $entity_values);
  }

  /**
   * @param $properties
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadDependency($properties, $referenced_field_name = NULL) {
    if (empty($properties['bundle'])) {
      $properties['bundle'] = static::BUNDLE_ID_DEPENDENCY;
    }
    $entity_dependencies = $this->entityHelper
      ->getStorage(static::ENTITY_TYPE_ID_RELATION)
      ->loadByProperties($properties);

    if (empty($referenced_field_name)) {
      return $entity_dependencies;
    }

    $entity_references = [];
    foreach ($entity_dependencies as $entity_dependency) {
      $entity_reference = $entity_dependency->get($referenced_field_name)->entity;
      $entity_references[$entity_reference->id()] = $entity_reference;
    }

    return $entity_references;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $referencing_entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createDependencyForEntity(ContentEntityInterface $referencing_entity) {

    foreach ($referencing_entity->getFieldDefinitions() as $field_name => $field_definition) {

      if (!method_exists($field_definition, 'getThirdPartySettings')) {
        continue;
      }

      if (!$settings = $field_definition->getThirdPartySettings('bd')) {
        continue;
      }

      if (empty($settings['dependency']['type'])) {
        continue;
      }

      $target_entity_type_id = $settings['dependency']['type'];

      $relation_storage = $this->entityHelper->getStorage('relation');

      foreach ($referencing_entity->get($field_name) as $delta => $field_item) {

        $referenced_entity = $field_item->entity;

        $entity_values = [];

        $entity_values['bundle'] = 'dependency';

        $target_entity_id = "{$referencing_entity->getEntityTypeId()}.{$referencing_entity->id()}.{$referenced_entity->getEntityTypeId()}.{$referenced_entity->id()}.{$target_entity_type_id}";

        $entity_values['field_id'] = $target_entity_id;

        if ($dependency = $relation_storage->loadByProperties($entity_values)) {
          $dependency = reset($dependency);
        }
        else {
          $dependency = $relation_storage->create($entity_values);
        }

        $add_entity_values['source'] = [
          'target_type' => $referencing_entity->getEntityTypeId(),
          'target_id' => $referencing_entity->id(),
        ];

        $add_entity_values['target'] = [
          'target_type' => $referenced_entity->getEntityTypeId(),
          'target_id' => $referenced_entity->id(),
        ];

        $load_properties = [
          'uuid' => $target_entity_type_id,
        ];

        if ($target_entity_type_id_entity = $this->entityHelper->getStorage('taxonomy_term')->loadByProperties($load_properties)) {
          $target_entity_type_id_entity = reset($target_entity_type_id_entity);
        }
        else {
          continue;
        }

        $add_entity_values['field_type'] = $target_entity_type_id_entity->id();

        foreach ($add_entity_values as $field_name => $field_values) {
          $dependency->set($field_name, $field_values);
        }

        $dependency->save();

      }

    }

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $referencing_entity
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteDependencyForEntity(EntityInterface $referencing_entity) {

    if ($entity_dependent_list = $this->getRelationsSourceByEntity($referencing_entity)) {
      foreach ($entity_dependent_list as $entity_dependency) {

        $source_entity_type_id = $entity_dependency->target->target_type;
        $source_entity_id = $entity_dependency->target->target_id;

        if (!$target_entity_type_id = $entity_dependency->field_type->entity->uuid->value) {
          \Drupal::logger('entity')->warning("Missing dependency type.");
        }

        try {

          if ($dependent_entity = $this->entityHelper->getStorage($source_entity_type_id)->load($source_entity_id)) {
            if ($target_entity_type_id == static::DEPENDENCY_TYPE_DELETE_REFERENCED) {
              $dependent_entity->delete();
            }
            else {
              // @todo handle other dependency type scenarios.
            }
          }

        }
        catch (\Exception $e) {
        }

        // Delete the dependency itself.
        $entity_dependency->delete();

      }
    }
  }

}
