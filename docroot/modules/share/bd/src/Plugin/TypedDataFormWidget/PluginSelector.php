<?php

namespace Drupal\bd\Plugin\TypedDataFormWidget;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\typed_data\Form\SubformState;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Plugin implementation of the 'select' widget.
 *
 * @TypedDataFormWidget(
 *   id = "plugin_selector",
 *   label = @Translation("Plugin selector"),
 *   description = @Translation("A plugin selector and config element."),
 *   data_type = {"plugin_instance"}
 * )
 */
class PluginSelector extends Base {

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
    $subform_parents = $form_state->get('subform_parents');

    // $plugin_form_parents = $subform_parents;
    $plugin_form_parents = [];
    $plugin_form_parents[] = 'value';
    $form_state_values = $form_state->getValues();

    $widget_values = $data->getValue() ?: [];

    $plugin_id = isset($widget_values['plugin_id']) ? $widget_values['plugin_id'] : NULL;
    $plugin_config = isset($widget_values['plugin_config']) ? $widget_values['plugin_config'] : [];

    $plugin_type_id = $this->configuration['plugin_type'];
    $label = isset($this->configuration['label']) ? $this->configuration['label'] : 'Plugin';
    $description = isset($this->configuration['description']) ? $this->configuration['description'] : NULL;

    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = \Drupal::service('plugin.plugin_type_manager');

    $plugin_type = $plugin_type_manager->getPluginType($plugin_type_id);

    $plugin_manager = \Drupal::service($plugin_type->getPluginManagerServiceName());

    $options_plugin = [];
    foreach ($plugin_manager->getDefinitions() as $plugin_id_all => $plugin_definition_all) {
      if (!is_array($plugin_definition_all)) {
        continue;
      }
      if (isset($plugin_definition_all['label'])) {
        $plugin_label = "{$plugin_definition_all['label']} ({$plugin_id_all})";
      }
      elseif (isset($plugin_definition_all['admin_label'])) {
        $plugin_label = "{$plugin_definition_all['admin_label']} ({$plugin_id_all})";
      }
      else {
        $plugin_label = $plugin_id_all;
      }
      $options_plugin[$plugin_id_all] = $plugin_label;
    }

    $plugin_id_name = implode("--", $subform_parents);
    $plugin_id_name .= "--plugin-id";
    $form['value']['plugin_id']['#name'] = $plugin_id_name;

    $id = implode('--', $subform_parents);
    $ajax_wrapper_id = "ajax--wrapper--{$id}";
    $form['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['value'] = [
      '#type' => 'details',
      '#title' => $this->t($label),
      '#description' => $description,
      '#open' => TRUE,
      '#tree' => TRUE,
      '#parents' => $subform_parents,
    ];

    $form['value']['plugin_id'] = [
      '#type' => 'select2',
      '#title' => $this->t('Plugin'),
      '#default_value' => $plugin_id,
      '#options' => $options_plugin,
      '#required' => $data->getDataDefinition()->isRequired(),
      '#disabled' => $data->getDataDefinition()->isReadOnly(),
      '#parents' => array_merge($plugin_form_parents, ['plugin_id']),
    ];

    if (!isset($this->configuration['configurable']) || $this->configuration['configurable'] == TRUE) {

      $form['value']['plugin_id']['#ajax'] = [
        'callback' => [$this, 'ajaxOpPluginId'],
        'wrapper' => $ajax_wrapper_id,
      ];

      if (!empty($plugin_id)) {

        /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
        $typed_config_manager = \Drupal::service('config.typed');
        $plugin_instance = $typed_config_manager->createPluginInstance($plugin_type_id, $plugin_id, $plugin_config);

        if ($plugin_instance instanceof PluginFormInterface) {

          $plugin_form_parents_config = array_merge($plugin_form_parents, ['plugin_config']);
          // $plugin_form_parents = [];
          // $plugin_form_parents[] = 'value';
          // $plugin_form_parents[] = 'plugin_config';
          // $plugin_form_parents[] = 'plugin_config';
          $form['value']['plugin_config'] = [
            '#tree' => TRUE,
            '#parents' => $plugin_form_parents_config,
          ];
          $subform_state_subform_state = SubformState::createForSubform($form['value']['plugin_config'], $form, $form_state);
          $form['value']['plugin_config'] = $plugin_instance->buildConfigurationForm($form['value']['plugin_config'], $subform_state_subform_state);
          $this->recurseRemoveParents($form['value']['plugin_config']);
          $form['value']['plugin_config']['#tree'] = TRUE;
          $form['value']['plugin_config']['#parents'] = $plugin_form_parents_config;
        }

      }

    }

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxOpPluginId(array $form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];

    array_pop($parents);
    array_pop($parents);

    $element = NestedArray::getValue($form, $parents);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(TypedDataInterface $data, SubformStateInterface $form_state) {

    $value = $form_state->getValue('value');
    if (isset($value['plugin_id']) && ($value['plugin_id'] === '')) {
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

    $definitions['plugin_id'] = ContextDefinition::create('plugin_id')
      ->setLabel($this->t('Plugin ID'));

    $definitions['plugin_config'] = ContextDefinition::create('plugin_configuration')
      ->setLabel($this->t('Plugin configuration'));

    return $definitions;
  }

}
