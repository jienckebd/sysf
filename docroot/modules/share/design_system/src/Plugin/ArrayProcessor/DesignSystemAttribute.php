<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Attaches standard entity attributes.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "design_system_attribute",
 *   label = @Translation("Entity attributes"),
 *   description = @Translation("Attaches standard entity attributes."),
 * )
 */
class DesignSystemAttribute extends Base {

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $context['entity'];

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    if ($attribute_field_name = $this->designSystem->getOption('attribute.entity_field_name')) {
      return;
    }

    foreach ($attribute_field_name as $field_name => $attribute_name) {

      if (!$entity->hasField($field_name)) {
        continue;
      }

      $entity_field = $entity->get($field_name);
      if ($entity_field->isEmpty()) {
        continue;
      }

      $field_value = $entity_field->value;

      if (stripos($attribute_name, '__') !== FALSE) {
        [
          $attribute_name,
          $attribute_value,
        ] = explode('__', $attribute_name);

        if ($attribute_name == 'class_prefix') {
          // This is a class prefix. Prefix with right side and use field
          // value.
          if (is_string($build['#attributes']['class'])) {
            $build['#attributes']['class'] = [$build['#attributes']['class']];
          }
          $build['#attributes']['class'][] = "{$attribute_value}{$field_value}";
        }
        elseif ($attribute_name == 'style') {
          $build['#attributes']['style'][$attribute_value] = $field_value;
        }
        else {
          $build['#attributes'][$attribute_name][] = $attribute_value;
        }

        continue;
      }

      $cardinality = $entity_field->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality();

      if ($cardinality != 1) {
        foreach ($entity_field as $field_item) {
          $build['#attributes'][$attribute_name][] = $field_item->value;
        }
      }
      else {
        $build['#attributes'][$attribute_name] = $entity_field->value;
      }

    }

  }

}
