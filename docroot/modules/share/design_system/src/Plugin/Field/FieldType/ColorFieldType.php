<?php

namespace Drupal\design_system\Plugin\Field\FieldType;

use Drupal\color_field\Plugin\Field\FieldType\ColorFieldType as Base;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Extends color field type.
 */
class ColorFieldType extends Base {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['color']['length'] = 255;
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // Ignore color_field preSave method that converts to uppercase and prefixes
    // with #.
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = [];
    // The color field module stores hex values but we want to store friendly
    // labels that can be used in class names like "primary". So remove
    // validation constraints that perform regex to confirm a hex value.
    return $constraints;
  }

}
