<?php

namespace Drupal\bd\Plugin\TypedDataFormWidget;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\typed_data\Form\SubformState;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Plugin implementation of the 'entity_reference' widget.
 *
 * @TypedDataFormWidget(
 *   id = "field_item_list",
 *   label = @Translation("Field item list"),
 *   description = @Translation("Provides a field item list."),
 *   data_type = {"list"}
 * )
 */
class FieldItemList extends Base {

  /**
   * The entity field widget plugin manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $fieldWidgetPluginManager;

  /**
   * The entity field widget.
   *
   * @var \Drupal\Core\Field\WidgetInterface
   */
  protected $fieldWidget;

  /**
   * The field items.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $fieldItems;

  /**
   * FieldItemList constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   * @param \Drupal\Core\Field\WidgetPluginManager $field_widget_plugin_manager
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TypedDataManagerInterface $typed_data_manager,
    WidgetPluginManager $field_widget_plugin_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $typed_data_manager);
    $this->fieldWidgetPluginManager = $field_widget_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('typed_data_manager'),
      $container->get('plugin.manager.field.widget')
    );
  }

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

    if (!in_array($form_state->getFormObject()->getFormId(), ['field_config_edit_form'])) {
      return $form;
    }

    /** @var \Drupal\field\FieldConfigInterface $entity_field_config */
    $entity_field_config = $form_state->getFormObject()->getEntity();

    $ids = (object) [
      'entity_type' => $entity_field_config->getTargetEntityTypeId(),
      'bundle' => $entity_field_config->getTargetBundle(),
      'entity_id' => NULL,
    ];
    $entity_dummy = _field_create_entity_from_ids($ids);

    $field_name = $entity_field_config->getName();

    /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
    $field_items = $entity_dummy->get($field_name);

    $third_party_settings = $entity_field_config->getThirdPartySettings('bd');

    // Clear any existing default value widget.
    if ($form_state->get('default_value_widget')) {
      $storage = $form_state->getStorage();
      unset($storage['default_value_widget']);
      $form_state->setStorage($storage);
    }

    $default_value = $data->getValue();

    if (isset($default_value['ief_id'])) {
      unset($default_value['ief_id']);
    }

    if (!empty($default_value)) {
      $field_items->setValue($default_value);
    }

    $field_item = $field_items->first() ?: $field_items->appendItem();

    if (!empty($third_party_settings['overview']['default_field_widget']['widget']['value']['plugin_id'])) {
      // From current form state.
      $default_widget_type = $third_party_settings['overview']['default_field_widget']['widget']['value']['plugin_id'];
    }
    elseif (!empty($third_party_settings['overview']['default_field_widget']['plugin_id'])) {
      // From saved entity.
      $default_widget_type = $third_party_settings['overview']['default_field_widget']['plugin_id'];
    }
    else {
      // Otherwise get default widget from field type plugin.
      $default_widget_type = NULL;
    }

    $configuration = [
      'type' => $default_widget_type,
      'settings' => [],
      'third_party_settings' => [],
    ];

    // Setting prepare to TRUE will get default widget type from field type if
    // not provided.
    $widget = $this->fieldWidgetPluginManager->getInstance([
      'field_definition' => $field_item->getFieldDefinition(),
      'form_mode' => '_custom',
      'prepare' => TRUE,
      'configuration' => $configuration,
    ]);
    $this->fieldWidget = $widget;
    $this->fieldItems = $field_items;

    $widget_default_settings = $widget::defaultSettings() ?: [];
    $widget->setSettings($widget_default_settings);

   # $subform_parents = $form_state->get('subform_parents');
    $subform_parents = [];

    $widget_parents = $subform_parents;
    $widget_parents[] = $field_name;

    $form = [
      '#tree' => TRUE,
      '#parents' => $subform_parents,
    ];

    $form[$field_name] = [
      '#tree' => TRUE,
      '#parents' => $widget_parents,
    ];

    $mock_form = [];

   # $subform_state = SubformState::createForSubform($form, $form_state->getCompleteForm(), $form_state->getCompleteFormState());
    $form[$field_name] = $widget->form($field_items, $form, $form_state);

    // Required for text_long to support input for format without making text
    // required too.
    if ($widget->getPluginId() == 'text_textarea') {
      $form[$field_name]['widget'][0]['#required'] = FALSE;
    }

    $this->recurseRemoveParents($form);
    $form['#parents'] = $subform_parents;
    $form[$field_name]['#parents'] = $widget_parents;

    if (isset($form[$field_name]['widget']['#field_parents'])) {
      $form[$field_name]['widget']['#field_parents'] = $widget_parents;
    }
//
//    if (!empty($form[$field_name]['widget']['#ief_id'])) {
//      $form[$field_name]['widget']['ief_id'] = [
//        '#type' => 'hidden',
//        '#value' => $form[$field_name]['widget']['#ief_id'],
//      ];
//      $form[$field_name]['widget']['ief_default_values'] = [
//        '#type' => 'hidden',
//        '#value' => serialize($default_value),
//      ];
//    }

    $this->fieldName = $field_name;
    $this->form = $form;
#    $this->subformState = $subform_state;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(TypedDataInterface $data, SubformStateInterface $form_state) {
    if (empty($this->fieldWidget)) {
      return;
    }

    // Form state values have an extra "widget" in parents.
    $values = $form_state->getValues();
   # $values = reset($values);
    $new_values = [];
    $new_values[$this->fieldName] = $values;
    $form_state->setValues($new_values);

    // Required for IEF.
    $this->form['#parents'] = $form_state->get('subform_parents');
    $triggering_element = &$form_state->getTriggeringElement();
    $triggering_element['#ief_submit_trigger'] = TRUE;
    $this->fieldWidget->extractFormValues($this->fieldItems, $this->form, $form_state);
    $triggering_element['#ief_submit_trigger'] = FALSE;

    $data->setValue($this->fieldItems->getValue());
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
