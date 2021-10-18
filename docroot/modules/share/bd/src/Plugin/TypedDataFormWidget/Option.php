<?php

namespace Drupal\bd\Plugin\TypedDataFormWidget;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\typed_data\Form\SubformState;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Plugin implementation of the 'select' widget.
 *
 * @TypedDataFormWidget(
 *   id = "option",
 *   label = @Translation("Option"),
 *   description = @Translation("Provides options."),
 *   data_type = {"option"}
 * )
 */
class Option extends Base {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'label' => NULL,
      'description' => NULL,
      'empty_option' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(TypedDataInterface $data, SubformStateInterface $form_state) {
    $form = SubformState::getNewSubForm();

    /** @var \Drupal\bd\PluginManager\EntityPluginManager $plugin_manager_options_provider */
    $plugin_manager_options_provider = \Drupal::service('plugin.manager.options_provider');
    $plugin_id = $this->configuration['option']['plugin_id'];
    $plugin_config = isset($this->configuration['option']['plugin_config']) ? $this->configuration['option']['plugin_config'] : [];
    $plugin_options_provider = $plugin_manager_options_provider->createInstance($plugin_id, $plugin_config);

    $form['value'] = [
      '#type' => 'select2',
      '#title' => $this->configuration['label'] ?: $data->getDataDefinition()->getLabel(),
      '#description' => $this->configuration['description'] ?: $data->getDataDefinition()->getDescription(),
      '#default_value' => $data->getValue(),
      '#multiple' => $data instanceof ListInterface,
      '#empty_option' => $this->configuration['empty_option'],
      '#empty_value' => '',
      '#options' => $plugin_options_provider->getOption(),
      '#required' => $data->getDataDefinition()->isRequired(),
      '#disabled' => $data->getDataDefinition()->isReadOnly(),
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

    $definitions['plugin_id'] = ContextDefinition::create('string')
      ->setLabel($this->t('Plugin ID'));

    $definitions['plugin_config'] = ContextDefinition::create('string')
      ->setLabel($this->t('Plugin config'));

    return $definitions;
  }

}
