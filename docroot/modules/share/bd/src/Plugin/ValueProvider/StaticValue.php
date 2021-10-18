<?php

namespace Drupal\bd\Plugin\ValueProvider;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides computed field values based on related entity values.
 *
 * @ValueProvider(
 *   plugin_type = "value_provider",
 *   id = "static_value",
 *   label = @Translation("Static value"),
 *   description = @Translation("Provide a static value."),
 * )
 */
class StaticValue extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['inner'] = [
      '#type' => 'container',
    ];

    $element['inner']['#process'][] = [$this, 'processStaticValue'];

    return $element;
  }

  /**
   *
   */
  public function processStaticValue(array $element, FormStateInterface $form_state, array &$complete_form) {

    $element['value'] = [
      '#type' => 'entity_autocomplete',
      '#title' => 'entity',
      '#target_type' => 'dom',
    ];

    return $element;
  }

}
