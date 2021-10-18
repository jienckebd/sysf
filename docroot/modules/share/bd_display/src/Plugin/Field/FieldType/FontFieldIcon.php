<?php

namespace Drupal\bd_display\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface as StorageDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\font_field_icon\Plugin\Field\FieldType\FontFieldIcon as Base;

/**
 * Overrides font field type from font_field_icon module.
 */
class FontFieldIcon extends Base {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(StorageDefinition $storage) {
    $properties = parent::propertyDefinitions($storage);
    $properties['font_field_icon_link_text'] = DataDefinition::create('string')
      ->setLabel(t('Text of link'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(StorageDefinition $storage) {
    $schema = parent::schema($storage);
    $schema['columns']['font_field_icon_link_text'] = [
      'type' => 'char',
      'length' => 255,
    ];
    return $schema;
  }

}
