<?php

namespace Drupal\bd\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem as Base;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Extends entity reference field item.
 */
class EntityReferenceItem extends Base {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    if ($field_definition->getSetting('schema__derivative')) {
      $schema['columns']['derivative'] = [
        'description' => 'The derivative ID.',
        'type' => 'varchar_ascii',
        'length' => 255,
        'not null' => FALSE,
      ];
    }

    if ($field_definition->getSetting('schema__base_target_id')) {
      // Copy the field type from parent to support either int or varchar
      // reference values.
      $schema['columns']['base_target_id'] = $schema['columns']['target_id'];
      $schema['columns']['base_target_id']['description'] = 'The ID of the base entity.';
      $schema['columns']['base_target_id']['not null'] = FALSE;
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['derivative'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    // Copy the field type from parent to support either int or varchar
    // reference values.
    $properties['base_target_id'] = $properties['target_id'];
    $properties['base_target_id']->setLabel(new TranslatableMarkup('Base Target ID'));
    $properties['base_target_id']->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'schema__derivative' => NULL,
      'schema__base_target_id' => NULL,
      'deriver' => NULL,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $value = parent::generateSampleValue($field_definition);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $element['schema__derivative'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Derivative'),
      '#description' => $this->t('Add the derivative field to schema.'),
      '#default_value' => $this->getSetting('schema__derivative'),
    ];

    $element['schema__base_target_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Base Target ID'),
      '#description' => $this->t('Add the base_target_id field to schema.'),
      '#default_value' => $this->getSetting('schema__base_target_id'),
    ];

    /** @var \Drupal\bd\PluginManager $plugin_manager_deriver */
    $plugin_manager_deriver = \Drupal::service('plugin.manager.entity_reference_deriver');
    $options = [];
    foreach ($plugin_manager_deriver->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }

    $element['deriver'] = [
      '#type' => 'select',
      '#title' => $this->t('Deriver'),
      '#description' => $this->t('Select a deriver plugin.'),
      '#default_value' => $this->getSetting('deriver'),
      '#options' => $options,
      '#normalize' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
  }

}
