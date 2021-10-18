<?php

namespace Drupal\bd\Plugin\ValueProvider;

use Drupal\bd\Plugin\EntityPluginBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Base class for value provider plugins.
 */
abstract class Base extends EntityPluginBase {

  /**
   * @return array
   */
  public function getValue(FieldableEntityInterface $entity, FieldConfigInterface $field_config) {
    if (is_string($this->configuration['field_items'])) {
      $this->configuration['field_items'] = [$this->configuration['field_items']];
    }
    return $this->processValues($entity, $field_config, $this->configuration['field_items']);
  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param \Drupal\field\FieldConfigInterface $field_config
   * @param array $values
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processValues(FieldableEntityInterface $entity, FieldConfigInterface $field_config, array $values) {

    $field_type = $field_config->getType();

    if (!empty($this->configuration['clone_entity_reference'])) {

      foreach ($values as $delta => &$field_item_values) {
        if (empty($field_item_values['target_type'])) {
          continue;
        }

        if ($field_type == 'entity_reference_revisions') {
          $base_entity = $this->entityHelper
            ->getStorage($field_item_values['target_type'])
            ->loadRevision($field_item_values['target_revision_id']);
        }
        else {
          $base_entity = $this->entityHelper
            ->getStorage($field_item_values['target_type'])
            ->load($field_item_values['target_id']);
        }

        if (empty($base_entity)) {
          continue;
        }

        $duplicate_entity = $base_entity->createDuplicate();
        $field_item_values = [
          'entity' => $duplicate_entity,
        ];

      }

    }

    return $values;
  }

}
