<?php

namespace Drupal\bd\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'entity_serialized' field type.
 *
 * @FieldType(
 *   id = "entity_serialized",
 *   label = @Translation("Entity Serialized"),
 *   description = @Translation("Provides an entity serialized field type."),
 *   category = @Translation("Entity"),
 *   default_formatter = "basic_string",
 *   default_widget = "string_textarea"
 * )
 */
class EntitySerialized extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'The serialized value.',
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

}
