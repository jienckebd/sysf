<?php

namespace Drupal\bd\Plugin\TypedDataFormWidget;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\typed_data\Form\SubformState;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Plugin implementation of the 'entity_reference' widget.
 *
 * @TypedDataFormWidget(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   description = @Translation("Provides entity references."),
 *   data_type = {"entity_reference"}
 * )
 */
class EntityReference extends Base {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $default_configuration['target_id'] = NULL;
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function form(TypedDataInterface $data, SubformStateInterface $form_state) {

    $form = SubformState::getNewSubForm();

    $entity_type_id = $this->configuration['target_type'];
    $bundle_id = isset($this->configuration['bundle']) ? $this->configuration['bundle'] : NULL;

    if (is_array($entity_type_id)) {
      return $form;
    }

    $entity_helper = \Drupal::service('entity.helper');
    $entity_type = $entity_helper->getDefinition($entity_type_id);
    $entity_storage = $entity_helper->getStorage($entity_type_id);

    $option = [];

    if (!empty($bundle_id)) {
      $bundle_key = $entity_type->getKey('bundle');
      $entities = $entity_storage->loadByProperties([
        $bundle_key => $bundle_id,
      ]);
    }
    else {
      $entities = $entity_storage->loadMultiple();
    }

    foreach ($entities as $entity_id => $entity) {
      $label = $entity->label();
      $id = $entity->id();
      $option[$entity_id] = "{$label} ({$id})";
    }

    $form['value'] = [
      '#type' => 'select2',
      '#title' => $this->configuration['label'] ?: $data->getDataDefinition()->getLabel(),
      '#description' => $this->configuration['description'] ?: $data->getDataDefinition()->getDescription(),
      '#default_value' => $data->getValue(),
      '#target_type' => $entity_type_id,
      '#autocomplete' => FALSE,
      '#options' => $option,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(TypedDataInterface $data, SubformStateInterface $form_state) {
    // Ensure empty values correctly end up as NULL value.
    $value = $form_state->getValue('value');
    if ($value === '') {
      $value = NULL;
    }
    $data->setValue($value);
  }

  /**
   * {@inheritdoc}
   */
  public function flagViolations(TypedDataInterface $data, ConstraintViolationListInterface $violations, SubformStateInterface $formState) {
    foreach ($violations as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $formState->setErrorByName('value', $violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationDefinitions(DataDefinitionInterface $definition) {
    $definitions = parent::getConfigurationDefinitions($definition);

    $definitions['target_type'] = ContextDefinition::create('string')
      ->setLabel($this->t('Label'));

    return $definitions;
  }

}
