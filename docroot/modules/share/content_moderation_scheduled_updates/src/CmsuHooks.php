<?php

namespace Drupal\content_moderation_scheduled_updates;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Cmsu hooks.
 */
class CmsuHooks {

  /**
   * Implements hook_entity_bundle_field_info_alter().
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
   *   The array of bundle field definitions.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $bundle
   *   The bundle.
   *
   * @see hook_entity_bundle_field_info_alter()
   * @see content_moderation_scheduled_updates_entity_bundle_field_info_alter()
   */
  public function entityBundleFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type, string $bundle) {
    foreach ($fields as $fieldDefinition) {
      if ('entity_reference' !== $fieldDefinition->getType()) {
        continue;
      }

      if ('scheduled_update' !== $fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')) {
        continue;
      }

      $fieldDefinition->addConstraint('CmsuScheduledStateTransition', []);
    }
  }

}
