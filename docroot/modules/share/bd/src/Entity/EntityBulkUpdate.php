<?php

namespace Drupal\bd\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class EntityUpdate.
 */
class EntityBulkUpdate {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  protected $entityHelper;

  /**
   * EntityBulkUpdate constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   */
  public function __construct(
    EntityHelper $entity_helper
  ) {
    $this->entityHelper = $entity_helper;
  }

  /**
   * @param $entity_type_id
   * @param $field_name
   * @param $field_value
   * @param bool $only_update_empty_value
   * @param array $entity_values
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setFieldValue($entity_type_id, $field_name, $field_value, $only_update_empty_value = TRUE, $entity_values = []) {

    $entity_storage = $this->entityHelper->getStorage($entity_type_id);

    if (!empty($entity_values)) {
      $entities = $entity_storage->loadByProperties($entity_values);
    }
    else {
      $entities = $entity_storage->loadMultiple();
    }

    foreach ($entities as $entity) {

      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }

      if ($only_update_empty_value && !$entity->get($field_name)->isEmpty()) {
        continue;
      }

      $entity->set($field_name, $field_value);
      $this->entityHelper->getLogger()->notice("Updating entity field value on @entity_type_id @entity_id for field @field_name to value @field_value.", [
        '@entity_type_id' => $entity->getEntityTypeId(),
        '@entity_id' => $entity->id(),
        '@field_name' => $field_name,
        '@field_value' => $field_value,
      ]);
      $entity->save();

    }

  }

  /**
   * @param $entity_type_id
   * @param $field_name_a
   * @param $field_name_b
   * @param array $entity_values
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function swapFieldValue($entity_type_id, $field_name_a, $field_name_b, $entity_values = []) {

    $entity_storage = $this->entityHelper->getStorage($entity_type_id);

    if (!empty($entity_values)) {
      $entities = $entity_storage->loadByProperties($entity_values);
    }
    else {
      $entities = $entity_storage->loadMultiple();
    }

    foreach ($entities as $entity) {

      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }

      $field_value_a = $entity->get($field_name_a)->getValue();
      $field_value_b = $entity->get($field_name_b)->getValue();
      $entity->set($field_name_b, $field_value_a);
      $entity->set($field_name_a, $field_value_b);

      $this->entityHelper->getLogger()->notice("Swapping entity field values on @entity_type_id @entity_id for fields @field_name_a and @field_name_b.", [
        '@entity_type_id' => $entity->getEntityTypeId(),
        '@entity_id' => $entity->id(),
        '@field_name_a' => $field_name_a,
        '@field_name_b' => $field_name_b,
      ]);
      $entity->save();

    }

  }

  /**
   * @param $entity_type
   * @param array $bundles
   * @param array $field_value_mapping
   */
  public function byBundle($entity_type, array $bundles = [], array $field_value_mapping = []) {

    $entity_storage = $this->entityHelper
      ->getStorage($entity_type);
    $entity_definition = $this->entityHelper
      ->getDefinition($entity_type);

    $bundle_key = $entity_definition->getKey('bundle');
    foreach ($bundles as $bundle) {

      $entities = $entity_storage->loadByProperties([
        $bundle_key => $bundle,
      ]);

      foreach ($entities as $entity) {
        foreach ($field_value_mapping as $field_name => $field_values) {
          $entity->set($field_name, $field_values);
          $entity->save();
        }
      }

    }
  }

  /**
   * @param $entity_type
   * @param array $bundles
   * @param array $field_value_mapping
   */
  public function byEntityType($entity_type, array $field_value_mapping = []) {

    $entity_storage = $this->entityHelper
      ->getStorage($entity_type);
    $entity_definition = $this->entityHelper
      ->getDefinition($entity_type);

    $entities = $entity_storage->loadMultiple();

    foreach ($entities as $entity) {
      foreach ($field_value_mapping as $field_name => $field_values) {
        $entity->set($field_name, $field_values);
        $entity->save();
      }
    }
  }

}
