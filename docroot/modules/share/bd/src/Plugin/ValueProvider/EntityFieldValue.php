<?php

namespace Drupal\bd\Plugin\ValueProvider;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Provides computed field values based on related entity values.
 *
 * @ValueProvider(
 *   plugin_type = "value_provider",
 *   id = "entity_field_value",
 *   label = @Translation("Entity field value"),
 *   description = @Translation("Provides a field value from a related entity or itself."),
 * )
 */
class EntityFieldValue extends Base {

  /**
   * @return array
   */
  public function getValue(FieldableEntityInterface $entity, FieldConfigInterface $field_config) {
    $values = [];
    return $this->processValues($entity, $field_config, $this->configuration['field_items']);
  }

}
