<?php

namespace Drupal\autoref\Plugin\autoref\matcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\autoref\AutorefInterface;

/**
 * Matches entity based on strings on specified fields.
 *
 * @AutorefMatcher(
 *   id = "string_match",
 *   title = @Translation("String Match"),
 *   help = @Translation("Matches based on having a matching string on specified fields.")
 * )
 */
class StringMatch extends Base implements MatcherInterface {

  /**
   * {inheritdoc}.
   */
  public function matchEntity(AutorefInterface $autoref_entity, EntityInterface $target_entity, EntityInterface $entity) {

    $paragraph_storage = $this->entityHelper->getStorage('paragraph');
    $string_field_types = $this->getStringFieldTypes();

    if (!$autoref_entity->hasField('field_string')) {
      return;
    }

    $autoref_string_match = $autoref_entity->get('field_string')->getValue();

    if ($autoref_entity->field_entity_label->value) {
      $autoref_string_match[] = [
        'value' => $target_entity->label(),
      ];
    }

    // Check if field_name is empty or autoref_empty allows adding to populated entities.
    foreach ($autoref_entity->field_name_check->getValue() as $target_field_check_data) {

      $target_field_check = $this->fieldStorageConfigStorage->load($target_field_check_data['target_id']);
      $target_field_name = $target_field_check->getName();

      if (!$entity->hasField($target_field_name)) {
        continue;
      }

      if ($target_field_check->getType() == 'entity_reference_revisions') {
        foreach ($entity->get($target_field_check->getName())->getValue() as $paragraph_data) {
          $paragraph = $paragraph_storage->load($paragraph_data['target_id']);
          foreach ($paragraph->getFieldDefinitions() as $paragraph_field) {
            if (in_array($paragraph_field->getType(), $string_field_types)) {
              foreach ($paragraph->get($paragraph_field->getName())->getValue() as $field_values) {

                // Check string matches.
                foreach ($autoref_string_match as $string_data) {
                  if (strpos($field_values['value'], $string_data['value']) !== FALSE) {
                    return TRUE;
                  }
                }
              }
            }
          }
        }
      }
    }

    return FALSE;
  }

}
