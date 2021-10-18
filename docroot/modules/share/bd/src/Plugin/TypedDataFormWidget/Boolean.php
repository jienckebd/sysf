<?php

namespace Drupal\bd\Plugin\TypedDataFormWidget;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\typed_data\Form\SubformState;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of the 'boolean' widget.
 *
 * @TypedDataFormWidget(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("Provides a checkbox."),
 *   data_type = {"boolean"}
 * )
 */
class Boolean extends Base {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'label' => NULL,
      'description' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(TypedDataInterface $data, SubformStateInterface $form_state) {
    $form = SubformState::getNewSubForm();
    $form['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->configuration['label'] ?: $data->getDataDefinition()->getLabel(),
      '#description' => $this->configuration['description'] ?: $data->getDataDefinition()->getDescription(),
      '#default_value' => $data->getValue(),
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
    return $definitions;
  }

}
