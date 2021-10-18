<?php

namespace Drupal\bd\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * Field item list class for entity index field type.
 */
class EntityIndexFieldItemList extends FieldItemList {

  /**
   * The entity reference field types.
   *
   * @var array
   */
  const FIELD_TYPE_ENTITY_REFERENCE = [
    'entity_reference',
  ];

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $field = $this->getFieldDefinition();

    $settings = $field->getSettings();

    $entity = $this->getEntity();

    if (!empty($settings['index_all_field']) || empty($settings['field'])) {
      $field_definitions = $this->getEntity()->getFieldDefinitions();
      $settings['field'] = array_keys($field_definitions);
    }

    if (empty($settings['field'])) {
      return;
    }

    $value = [];
    foreach ($settings['field'] as $field_name) {

      if (!$entity->hasField($field_name)) {
        continue;
      }
      if ($entity->get($field_name)->isEmpty()) {
        continue;
      }

      $field_definition = $entity->getFieldDefinition($field_name);
      $field_type = $field_definition->getType();
      $index_value = [];

      foreach ($entity->get($field_name) as $delta => $field_item) {
        $index_value[$delta] = $field_item->getValue();

        if (in_array($field_type, static::FIELD_TYPE_ENTITY_REFERENCE)) {

          /** @var \Drupal\Core\Entity\EntityInterface $target_entity */
          $target_entity = $field_item->entity;

          if (empty($target_entity)) {
            continue;
          }

          // Store both ID and label.
          $index_value[$delta]['label'] = $target_entity->label();

        }

      }

      $value[$field_name] = $index_value;
    }

    $this->set(0, serialize($value));

  }

}
