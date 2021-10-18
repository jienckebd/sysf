<?php

namespace Drupal\design_system\Component;

use Drupal\inline_entity_form\ElementSubmit;
use Drupal\Core\Form\FormStateInterface;
use Drupal\design_system\DesignSystem;

/**
 * Integrates a component IEF with other Drupal elements.
 */
trait FormIntegrationTrait {

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $settings
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function attachSettingsForm(array &$element, FormStateInterface $form_state, array $settings) {
    $selected_field = $form_state->get('plugin_settings_edit');
    $user_input = $form_state->getUserInput();

    $element['#prefix'] = '<div id="ajax--wrapper--component-inline-form">';
    $element['#suffix'] = '</div>';

    $element['component_type'] = [
      '#type' => 'select',
      '#normalize' => TRUE,
      '#title' => $this->t('Component type'),
      '#options' => $this->designSystem->getOptionComponentType(),
      '#default_value' => isset($settings['component_type']) ? $settings['component_type'] : NULL,
      '#required' => FALSE,
      '#ajax' => [
        'callback' => [static::class, 'ajaxOpComponentTypeUpdate'],
        'wrapper' => 'ajax--wrapper--component-inline-form',
      ],
    ];

    if ($selected_component_type_id_from_form_state = $form_state->getValue(['fields', $selected_field, 'settings_edit_form', 'settings', 'component_type'])) {
      $selected_component_type_id = $selected_component_type_id_from_form_state;
    }
    elseif (!empty($user_input['options']['component_wrapper']['component_type'])) {
      $selected_component_type_id = $user_input['options']['component_wrapper']['component_type'];
    }
    elseif (!empty($settings['component_type'])) {
      $selected_component_type_id = $settings['component_type'];
    }

    if (!empty($selected_component_type_id)) {
      $element['component'] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => DesignSystem::ENTITY_TYPE_ID_COMPONENT,
        '#bundle' => $selected_component_type_id,
        '#form_mode' => 'inline',
        '#save_entity' => TRUE,
      ];

      if (!empty($settings['component'])) {
        $entity_component = \Drupal::service('entity.helper')->getStorage(DesignSystem::ENTITY_TYPE_ID_COMPONENT)->load($settings['component']);

        if (!empty($entity_component)) {
          $element['component']['#default_value'] = $entity_component;
        }
        else {
          \Drupal::logger('design')->warning("Component @entity_id is set but can't be loaded.", [
            '@entity_id' => $settings['component'],
          ]);
        }
      }
    }

    $element['component_existing'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => DesignSystem::ENTITY_TYPE_ID_COMPONENT,
      '#title' => $this->t('Existing component'),
    ];

    $element['#element_validate'][] = [$this, 'validateComponent'];
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateComponent(array $element, FormStateInterface $form_state) {

    if ($existing_component_id = $form_state->getValue(['options', 'component_wrapper', 'component_existing'])) {
      $component_id = $existing_component_id;
    }
    else {

      if (empty($element['component'])) {
        return;
      }

      $entity_component = $element['component']['#entity'];
      $entity_component->set('parent_type', 'external');
      ElementSubmit::doSubmit($element['component'], $form_state);
      $component_id = $entity_component->id();
    }
    $form_state->setValueForElement($element['component'], $component_id);

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function ajaxOpComponentTypeUpdate(array $form, FormStateInterface $form_state) {

    if ($selected_field = $form_state->get('plugin_settings_edit')) {
      $return = $form['fields'][$selected_field]['format']['format_settings']['settings'];
    }
    elseif (!empty($form['options']['component_wrapper'])) {
      $return = $form['options']['component_wrapper'];
    }
    else {
      $return = $form;
    }

    return $return;
  }

}
