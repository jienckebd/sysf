<?php

namespace Drupal\bd\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_index' field type.
 *
 * @FieldType(
 *   id = "entity_index",
 *   label = @Translation("Entity Index"),
 *   description = @Translation("Provides an entity index field type."),
 *   category = @Translation("Entity"),
 *   default_formatter = "basic_string",
 *   default_widget = "text_textarea",
 *   list_class = "\Drupal\bd\Plugin\Field\EntityIndexFieldItemList"
 * )
 */
class EntityIndex extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'field' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $blacklist_field_type = [
      'entity_index',
    ];

    $entity_type_id = $this->getFieldDefinition()->getTargetEntityTypeId();
    $bundle_id = $this->getFieldDefinition()->getTargetBundle();
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle_id);
    $options = [];
    foreach ($fields as $field_name => $field) {
      $field_type = $field->getType();
      if (in_array($field_type, $blacklist_field_type)) {
        continue;
      }
      $options[$field_name] = $field->getLabel();
    }

    $element['field'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields'),
      '#description' => $this->t('Select the fields that will be stored in the index.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('field'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsToConfigData(array $settings) {

    foreach ($settings['field'] as $key => $value) {
      if ($value === 0) {
        unset($settings['field'][$key]);
      }
    }

    return $settings;
  }

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
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'The indexed entity field values.',
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => FALSE,
        ],
      ],
    ];
  }

}
