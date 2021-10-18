<?php

namespace Drupal\bd\Plugin\TypedDataFormWidget;

use Drupal\Core\Render\Element;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\typed_data\Widget\FormWidgetBase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base class for typed data form widget plugins.
 */
abstract class Base extends FormWidgetBase {

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
  public function isApplicable(DataDefinitionInterface $definition) {

    $data_type = $definition->getDataType();
    $plugin_definition = $this->getPluginDefinition();

    if (empty($plugin_definition['data_type'])) {
      return FALSE;
    }

    return in_array($data_type, $plugin_definition['data_type']);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function form(TypedDataInterface $data, SubformStateInterface $form_state);

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
    return [
      'label' => ContextDefinition::create('string')
        ->setLabel($this->t('Label')),
      'description' => ContextDefinition::create('string')
        ->setLabel($this->t('Description')),
    ];
  }

  /**
   * @param array $element
   */
  public function recurseRemoveParents(array &$element) {

    foreach (Element::children($element) as $child_key) {

      $child = &$element[$child_key];

      if (is_array($child)) {
        if (isset($child['#parents'])) {
          unset($child['#parents']);
        }
        if (isset($child['#array_parents'])) {
          unset($child['#array_parents']);
        }
        $this->recurseRemoveParents($child);
      }
    }

  }

}
