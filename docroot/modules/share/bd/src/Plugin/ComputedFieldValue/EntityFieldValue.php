<?php

namespace Drupal\bd\Plugin\ComputedFieldValue;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\bd\Plugin\EntityPluginBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides computed field values based on related entity values.
 *
 * @ComputedFieldValue(
 *   plugin_type = "computed_field_value",
 *   id = "entity_field_value",
 *   label = @Translation("Entity field value"),
 *   description = @Translation("Derives field values from other entity field values."),
 * )
 */
class EntityFieldValue extends EntityPluginBase {

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return array
   */
  public function getComputedValue(FieldableEntityInterface $entity, FieldDefinitionInterface $field_definition) {

    $computed_field_selector = $this->configuration['value_selector'];
    $selector_pieces = explode('.', $computed_field_selector);

    $result = [];
    $this->recurseGetEntitySelectorValue($entity, $selector_pieces, $result);

    $items = [];

    if (!empty($result)) {
      $delta = 0;
      foreach ($result as $key => $values) {
        if (empty($values)) {
          continue;
        }
        foreach ($values as $subvalue) {
          $items[$delta] = $subvalue;
          $delta++;
        }
      }
    }

    return $items;
  }

  /**
   * @param $entity_selector
   * @param $selector_pieces
   * @param array $items
   */
  protected function recurseGetEntitySelectorValue($entity_selector, $selector_pieces, array &$items = []) {

    $selector_piece = array_shift($selector_pieces);

    if ($entity_selector instanceof FieldItemListInterface) {
      foreach ($entity_selector as $delta => $field_item) {
        $result = $field_item->{$selector_piece};

        // Process data derivers.
        if ($result instanceof EntityPluginBase) {
          $result = $result->process();
        }

        if (!empty($selector_pieces)) {
          $this->recurseGetEntitySelectorValue($result, $selector_pieces, $items);
        }
        else {
          $items[] = $result;
        }
      }
    }
    else {

      $result = $entity_selector->{$selector_piece};
      if (!empty($selector_pieces)) {
        $this->recurseGetEntitySelectorValue($result, $selector_pieces, $items);
      }
      else {
        $items[] = $result;
      }

    }

  }

}
