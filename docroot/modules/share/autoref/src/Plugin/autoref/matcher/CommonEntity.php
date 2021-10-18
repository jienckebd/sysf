<?php

namespace Drupal\autoref\Plugin\autoref\matcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\autoref\AutorefInterface;

/**
 * Matches entity based on common entities with criteria.
 *
 * @AutorefMatcher(
 *   id = "common_entity",
 *   title = @Translation("Common Entity"),
 *   help = @Translation("Matches based on having a common entity on a designated field.")
 * )
 */
class CommonEntity extends Base implements MatcherInterface {

  /**
   * {inheritdoc}.
   */
  public function matchEntity(AutorefInterface $autoref_entity, EntityInterface $target_entity, EntityInterface $entity) {
    foreach ($autoref_entity->get('field_name_check')->getValue() as $target_field_check_data) {
      $field_check = $this->fieldStorageConfigStorage->load($target_field_check_data['target_id']);
      $field_check_name = $field_check->getName();

      // Verify that the entity has the field we're checking.
      if (!$entity->hasField($field_check_name) || !$target_entity->hasField($field_check_name)) {
        continue;
      }

      if (array_intersect($this->getEntityFieldValues($entity, $field_check_name), $this->getEntityFieldValues($target_entity, $field_check_name))) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
