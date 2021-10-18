<?php

namespace Drupal\bd\Plugin\ValueProvider;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Provides computed field values based on related entity values.
 *
 * @ValueProvider(
 *   plugin_type = "value_provider",
 *   id = "callback",
 *   label = @Translation("Callback"),
 *   description = @Translation("Get a value from a provided callback."),
 * )
 */
class Callback extends Base {

  /**
   * @return array
   */
  public function getValue(FieldableEntityInterface $entity, FieldConfigInterface $field_config) {
    $values = [];
    return $this->processValues($entity, $field_config, $this->configuration['field_items']);
  }

}
