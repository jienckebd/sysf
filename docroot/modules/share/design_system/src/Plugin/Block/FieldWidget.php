<?php

/**
 * @file
 */

// Namespace Drupal\design_system\Plugin\Block;
//
// use Drupal\bd\Php\Arr;
// use Drupal\Component\Plugin\Factory\DefaultFactory;
// use Drupal\Component\Plugin\PluginHelper;
// use Drupal\Component\Utility\NestedArray;
// use Drupal\Core\Access\AccessResult;
// use Drupal\Core\Block\BlockBase;
// use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
// use Drupal\Core\Entity\EntityDisplayBase;
// use Drupal\Core\Entity\EntityFieldManagerInterface;
// use Drupal\Core\Entity\FieldableEntityInterface;
// use Drupal\Core\Extension\ModuleHandlerInterface;
// use Drupal\Core\Field\FieldDefinitionInterface;
// use Drupal\Core\Field\WidgetInterface;
// use Drupal\Core\Field\WidgetPluginManager;
// use Drupal\Core\Form\FormHelper;
// use Drupal\Core\Form\FormStateInterface;
// use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
// use Drupal\Core\Plugin\ContextAwarePluginInterface;
// use Drupal\Core\Session\AccountInterface;
// use Drupal\Core\StringTranslation\TranslatableMarkup;
// use Psr\Log\LoggerInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use Drupal\Core\Render\RendererInterface;
//
// **
// * Provides a block that renders a field from an entity.
// *
// * @Block(
// *   id = "field_widget",
// *   deriver = "\Drupal\design_system\Plugin\Derivative\FieldWidgetDeriver",
// * )
// *
// * @internal
// *   Plugin classes are internal.
// */
// class FieldWidget extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {
//
//  /**
//   * The entity field manager.
//   *.
/**
 * * @var \Drupal\Core\Entity\EntityFieldManagerInterface .*/
// */
//  protected $entityFieldManager;
//
//  /**
//   * The widget manager.
//   *
/**
 * * @var \Drupal\Core\Field\WidgetPluginManager .*/
// */
//  protected $widgetManager;
//
//  /**
//   * The entity type ID.
//   *
/**
 * * @var string .*/
// */
//  protected $entityTypeId;
//
//  /**
//   * The bundle ID.
//   *
/**
 * * @var string .*/
// */
//  protected $bundle;
//
//  /**
//   * The field name.
//   *
/**
 * * @var string .*/
// */
//  protected $fieldName;
//
//  /**
//   * The field definition.
//   *
/**
 * * @var \Drupal\Core\Field\FieldDefinitionInterface .*/
// */
//  protected $fieldDefinition;
//
//  /**
//   * The module handler.
//   *
/**
 * * @var \Drupal\Core\Extension\ModuleHandlerInterface .*/
// */
//  protected $moduleHandler;
//
//  /**
//   * The renderer.
//   *
/**
 * * @var \Drupal\Core\Render\RendererInterface .*/
// */
//  protected $renderer;
//
//  /**
//   * The logger.
//   *
/**
 * * @var \Psr\Log\LoggerInterface .*/
// */
//  protected $logger;
//
//  /**
//   * Constructs a new FieldWidget.
//   *
//   * @param array $configuration
//   *   A configuration array containing information about the plugin instance.
//   * @param string $plugin_id
//   *   The plugin ID for the plugin instance.
//   * @param mixed $plugin_definition
//   *   The plugin implementation definition.
//   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
//   *   The entity field manager.
//   * @param \Drupal\Core\Field\WidgetPluginManager $widget_manager
//   *   The widget manager.
//   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
//   *   The module handler.
//   * @param \Drupal\Core\Render\RendererInterface $renderer
//   *   The renderer.
//   * @param \Psr\Log\LoggerInterface $logger
//   *   The logger.
//   */
//  public function __construct(
//    array $configuration,
//    $plugin_id,
//    $plugin_definition,
//    EntityFieldManagerInterface $entity_field_manager,
//    WidgetPluginManager $widget_manager,
//    ModuleHandlerInterface $module_handler,
//    RendererInterface $renderer,
//    LoggerInterface $logger
//  ) {
//    $this->entityFieldManager = $entity_field_manager;
//    $this->widgetManager = $widget_manager;
//    $this->moduleHandler = $module_handler;
//    $this->renderer = $renderer;
//    $this->logger = $logger;
//
//    // Get the entity type and field name from the plugin ID.
//    [, $entity_type_id, $bundle, $field_name] = explode(static::DERIVATIVE_SEPARATOR, $plugin_id, 4);
//    $this->entityTypeId = $entity_type_id;
//    $this->bundle = $bundle;
//    $this->fieldName = $field_name;
//
//    parent::__construct($configuration, $plugin_id, $plugin_definition);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
//    return new static(
//      $configuration,
//      $plugin_id,
//      $plugin_definition,
//      $container->get('entity_field.manager'),
//      $container->get('plugin.manager.field.widget'),
//      $container->get('module_handler'),
//      $container->get('renderer'),
//      $container->get('logger.channel.design_system')
//    );
//  }
//
//  /**
//   * Gets the entity that has the field.
//   *
//   * @return \Drupal\Core\Entity\FieldableEntityInterface
//   *   The entity.
//   */
//  protected function getEntity() {
//    return $this->getContextValue('entity');
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getContextMapping() {
//    $configuration = PluginHelper::isConfigurable($this) ? $this->getConfiguration() : $this->configuration;
//    $mapping = isset($configuration['context_mapping']) ? $configuration['context_mapping'] : [];
//    if (empty($mapping)) {
//      $mapping['entity'] = 'layout_builder.entity';
//    }
//    return $mapping;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function build() {
//    $build = [
//      '#tree' => TRUE,
//      '#mock_field_widget_config' => $this->getConfiguration(),
//      '#mock_field_widget_name' => $this->fieldName,
//    ];
//    $build['#process'][] = [$this, 'processFieldWidget'];
//
//    // Throws warning in language_form_alter(). Will be adjusted in this process
//    // callback.
//    $build['#access'] = TRUE;
//
//    return $build;
//  }
//
//  /**
//   * Process callback used so form builder can attach parents.
//   *
//   * Parents are needed by widget subform state.
//   *
//   * @param array $element
//   * @param \Drupal\Core\Form\FormStateInterface $form_state
//   * @param array $complete_form
//   *
//   * @return array
//   */
//  public function processFieldWidget(array $element, FormStateInterface $form_state, array &$complete_form) {
//
//    $entity = $this->getEntity();
//    try {
//
//      $parents = $element['#parents'];
//
//      if (!$widget = $this->getWidget($parents, $form_state)) {
//        $this->logger->warning("Invalid field used for plugin: @plugin_id", [
//          '@plugin_id' => $this->getPluginId(),
//        ]);
//        return $element;
//      }
//
//      $widget_subform_state = $form_state;
//
//      $field_items = $entity->get($this->fieldName);
//      $field_items->filterEmptyItems();
//
//      // Reset parents. They're set in field widget handling.
//      $element['#parents'] = [];
//
//      $parents = $element['#parents'];
//      $array_parents = $element['#array_parents'];
//
//      $widget_build = $widget->form($field_items, $element, $widget_subform_state);
//      $element = $widget_build;
//      $element['#parents'] = $parents;
//      $element['#array_parents'] = $array_parents;
//      $element['#access'] = $field_items->access('edit');
//      $element['#mock_field_widget_name'] = $this->fieldName;
//      $element['#mock_field_widget_config'] = $this->getConfiguration();
//
//      \Drupal::formBuilder()->doBuildForm($form_state->getFormObject()->getFormId(), $element, $form_state);
//
//      if ($this->configuration['widget']['type'] == 'block_field_default') {
//        $this->processBlockFieldWidgetContextMapping($element);
//      }
//
//      $this->renderer->addCacheableDependency($element, $this->fieldDefinition);
//      $this->renderer->addCacheableDependency($element, $this->fieldDefinition->getFieldStorageDefinition());
//
//    }
//    catch (\Exception $e) {
//      $this->logger->warning('The field "%field" failed to render with the error of "%error".', ['%field' => $this->fieldName, '%error' => $e->getMessage()]);
//    }
//
//    if ($form_state->get('in_layout_builder')) {
//      Arr::recurseSetValues($element, [
//        '#disabled' => TRUE,
//      ]);
//    }
//
//    return $element;
//  }
//
//  /**
//   * @param array $element
//   */
//  protected function processBlockFieldWidgetContextMapping(array &$element) {
//    if (!empty($element['widget'][0]['settings']['context_mapping']['entity'])) {
//      $element['widget'][0]['settings']['context_mapping']['entity']['#value'] = '@design_system.context_provider.entity_display:display.view.entity:parent:parent:parent';
//      $element['widget'][0]['settings']['context_mapping']['entity']['#wrapper_attributes']['class'][] = 'visually-hidden';
//    }
//    if (!empty($element['widget'][0]['settings']['context_mapping']['view_mode'])) {
//      $element['widget'][0]['settings']['context_mapping']['view_mode']['#value'] = '@design_system.context_provider.entity_display:display.view.mode:parent:parent:parent';
//      $element['widget'][0]['settings']['context_mapping']['view_mode']['#wrapper_attributes']['class'][] = 'visually-hidden';
//    }
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getPreviewFallbackString() {
//    return new TranslatableMarkup('"@field" field', ['@field' => $this->getFieldDefinition()->getLabel()]);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  protected function blockAccess(AccountInterface $account) {
//    $entity = $this->getEntity();
//    return AccessResult::allowed();
//
//    // First consult the entity.
//    $access = $entity->access('edit', $account, TRUE);
//    if (!$access->isAllowed()) {
//      return $access;
//    }
//
//    // Check that the entity in question has this field.
//    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField($this->fieldName)) {
//      return $access->andIf(AccessResult::forbidden());
//    }
//
//    // Check field access.
//    $field = $entity->get($this->fieldName);
//    $access = $access->andIf($field->access('edit', $account, TRUE));
//    if (!$access->isAllowed()) {
//      return $access;
//    }
//
//    return $access;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function defaultConfiguration() {
//
//    $default_field_widget_plugin = $this->pluginDefinition['default_widget'];
//
//    if (!isset($this->fieldDefinition)) {
//      $this->getFieldDefinition();
//    }
//
//    if ($this->fieldDefinition instanceof ThirdPartySettingsInterface) {
//      if ($normalized_field_config = $this->fieldDefinition->getThirdPartySettings('bd')) {
//        if (!empty($normalized_field_config['overview']['default_field_widget']['plugin_id'])) {
//          $default_field_widget_plugin = $normalized_field_config['overview']['default_field_widget']['plugin_id'];
//        }
//      }
//    }
//
//    return [
//      'label_display' => FALSE, # Block label.
//      'widget' => [
//        'type' => $default_field_widget_plugin,
//        'settings' => [
//          'display_label' => TRUE, # Field widget label.
//        ],
//        'third_party_settings' => [],
//      ],
//    ];
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function blockForm($form, FormStateInterface $form_state) {
//    $config = $this->getConfiguration();
//
//    $form['widget'] = [
//      '#tree' => TRUE,
//      '#process' => [
//        [$this, 'widgetSettingsProcessCallback'],
//      ],
//    ];
//    $form['widget']['label'] = [
//      '#type' => 'select',
//      '#title' => $this->t('Label'),
//      // @todo This is directly copied from
//      //   \Drupal\field_ui\Form\EntityViewDisplayEditForm::getFieldLabelOptions(),
//      //   resolve this in https://www.drupal.org/project/drupal/issues/2933924.
//      '#options' => [
//        'above' => $this->t('Above'),
//        'inline' => $this->t('Inline'),
//        'hidden' => '- ' . $this->t('Hidden') . ' -',
//        'visually_hidden' => '- ' . $this->t('Visually Hidden') . ' -',
//      ],
//      '#default_value' => $config['widget']['label'],
//    ];
//
//    $form['widget']['type'] = [
//      '#type' => 'select',
//      '#title' => $this->t('Widget'),
//      '#options' => $this->getApplicablePluginOptions($this->getFieldDefinition()),
//      '#required' => TRUE,
//      '#default_value' => $config['widget']['type'],
//      '#ajax' => [
//        'callback' => [static::class, 'widgetSettingsAjaxCallback'],
//        'wrapper' => 'widget-settings-wrapper',
//      ],
//    ];
//
//    // Add the widget settings to the form via AJAX.
//    $form['widget']['settings_wrapper'] = [
//      '#prefix' => '<div id="widget-settings-wrapper">',
//      '#suffix' => '</div>',
//    ];
//
//    return $form;
//  }
//
//  /**
//   * Render API callback: builds the widget settings elements.
//   */
//  public function widgetSettingsProcessCallback(array &$element, FormStateInterface $form_state, array &$complete_form) {
//    if ($widget = $this->getWidget($element['#parents'], $form_state)) {
//
//      // Store the array parents for our element so that we can retrieve the
//      // widget settings in our AJAX callback.
//  #    $third_party_settings_parents =  array_merge($element['#parents'], ['third_party_settings']);;
//   #   $form_state->set('field_widget_third_party_settings_parents', $third_party_settings_parents);
// #      $form_state->set('field_block_parents', $third_party_settings_parents);
//
//      $element['settings_wrapper']['settings'] = $widget->settingsForm($complete_form, $form_state);
//      $element['settings_wrapper']['settings']['#parents'] = array_merge($element['#parents'], ['settings']);
//      $element['settings_wrapper']['third_party_settings'] = $this->thirdPartySettingsForm($widget, $this->getFieldDefinition(), $complete_form, $form_state);
//      $element['settings_wrapper']['third_party_settings']['#parents'] = array_merge($element['#parents'], ['third_party_settings']);
//      FormHelper::rewriteStatesSelector($element['settings_wrapper'], "fields[$this->fieldName][settings_edit_form]", 'settings[widget]');
//
//      $form_state->set('field_block_array_parents', $element['#array_parents']);
//
//    }
//    return $element;
//  }
//
//  /**
//   * Adds the widget third party settings forms.
//   *
//   * @param \Drupal\Core\Field\WidgetInterface $plugin
//   *   The widget.
//   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
//   *   The field definition.
//   * @param array $form
//   *   The (entire) configuration form array.
//   * @param \Drupal\Core\Form\FormStateInterface $form_state
//   *   The form state.
//   *
//   * @return array
//   *   The widget third party settings form.
//   */
//  protected function thirdPartySettingsForm(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
//    $settings_form = [];
//    // Invoke hook_field_widget_third_party_settings_form(), keying resulting
//    // subforms by module name.
//
//    $config = isset($this->configuration['widget']['third_party_settings']['design_system']) ? $this->configuration['widget']['third_party_settings']['design_system'] : [];
//
//    $form_state->set('design_system_third_party_settings', $config);
//
//    foreach ($this->moduleHandler->getImplementations('field_widget_third_party_settings_form') as $module) {
//      $settings_form[$module] = $this->moduleHandler->invoke($module, 'field_widget_third_party_settings_form', [
//        $plugin,
//        $field_definition,
//        EntityDisplayBase::CUSTOM_MODE,
//        $form,
//        $form_state,
//      ]);
//    }
//    return $settings_form;
//  }
//
//  /**
//   * Render API callback: gets the layout settings elements.
//   */
//  public static function widgetSettingsAjaxCallback(array $form, FormStateInterface $form_state) {
//    $widget_array_parents = $form_state->get('field_block_array_parents');
//    return NestedArray::getValue($form, array_merge($widget_array_parents, ['settings_wrapper']));
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function blockSubmit($form, FormStateInterface $form_state) {
//    $this->configuration = $form_state->getValues();
//  }
//
//  /**
//   * Gets the field definition.
//   *
//   * @return \Drupal\Core\Field\FieldDefinitionInterface
//   *   The field definition.
//   */
//  protected function getFieldDefinition() {
//    if (empty($this->fieldDefinition)) {
//      $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $this->bundle);
//      $this->fieldDefinition = $field_definitions[$this->fieldName];
//    }
//    return $this->fieldDefinition;
//  }
//
//  /**
//   * Returns an array of applicable widget options for a field.
//   *
//   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
//   *   The field definition.
//   *
//   * @return array
//   *   An array of applicable widget options.
//   *
//   * @see \Drupal\field_ui\Form\EntityDisplayFormBase::getApplicablePluginOptions()
//   */
//  protected function getApplicablePluginOptions(FieldDefinitionInterface $field_definition) {
//    $options = $this->widgetManager->getOptions($field_definition->getType());
//    $applicable_options = [];
//    foreach ($options as $option => $label) {
//      $plugin_class = DefaultFactory::getPluginClass($option, $this->widgetManager->getDefinition($option));
//      if ($plugin_class::isApplicable($field_definition)) {
//        $applicable_options[$option] = $label;
//      }
//    }
//    return $applicable_options;
//  }
//
//  /**
//   * Gets the widget object.
//   *
//   * @param array $parents
//   *   The #parents of the element representing the widget.
//   * @param \Drupal\Core\Form\FormStateInterface $form_state
//   *   The current state of the form.
//   *
//   * @return \Drupal\Core\Field\WidgetInterface
//   *   The widget object.
//   */
//  protected function getWidget(array $parents, FormStateInterface $form_state) {
//    // Use the processed values, if available.
//    $configuration = NULL;
//    if (count($parents) > 1) {
//      $configuration = NestedArray::getValue($form_state->getValues(), $parents);
//    }
//    if (!$configuration || !is_array($configuration) || empty($configuration['type'])) {
//      // Next check the raw user input.
//      $configuration = NestedArray::getValue($form_state->getUserInput(), $parents);
//      if (!$configuration || !is_array($configuration) || empty($configuration['type'])) {
//        // If no user input exists, use the default values.
//        $configuration = $this->getConfiguration()['widget'];
//      }
//    }
//
//    return $this->widgetManager->getInstance([
//      'configuration' => $configuration,
//      'field_definition' => $this->getFieldDefinition(),
//      'view_mode' => EntityDisplayBase::CUSTOM_MODE,
//      'prepare' => TRUE,
//    ]);
//  }
//
// }
