<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\Component\Plugin\PluginHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\design_system\DesignSystem;

/**
 * Provides a block that renders a field from an entity.
 *
 * @Block(
 *   id = "component",
 *   category = @Translation("Component"),
 *   entity_type = "component",
 *   deriver = "\Drupal\design_system\Plugin\Derivative\EntityTypeBundle",
 * )
 */
class Component extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new Component block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The formatter manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DesignSystem $design_system,
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    FormatterPluginManager $formatter_manager,
    ModuleHandlerInterface $module_handler,
    LoggerInterface $logger
  ) {
    $this->designSystem = $design_system;
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->formatterManager = $formatter_manager;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('design.system'),
      $container->get('entity.helper'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('module_handler'),
      $container->get('logger.channel.design_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getContextMapping() {
    $configuration = PluginHelper::isConfigurable($this) ? $this->getConfiguration() : $this->configuration;
    $mapping = isset($configuration['context_mapping']) ? $configuration['context_mapping'] : [];
    if (empty($mapping)) {
      $mapping['entity'] = 'layout_builder.entity';
    }
    return $mapping;
  }

  /**
   * Gets the entity that has the field.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity.
   */
  protected function getEntity() {

    // @todo workaround for bad context logic.
    if ($this->configuration['context_mapping']['entity'] == '@user.current_user_context:current_user') {
      $this->setContextValue('entity', entity_load('user', \Drupal::currentUser()->id()));
    }

    return $this->getContextValue('entity');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if (empty($this->configuration['component'])) {
      return $build;
    }

    $entity_id_component = $this->configuration['component'];

    if ($entity_id_component == 220) {
      $d = 1;
    }

    if (!$entity_component = $this->designSystem->getComponent($entity_id_component)) {
      return $build;
    }

    $entity_block_context = $this->getEntity();

    if (!empty($this->configuration['field_override'])) {

      $entity_component = $entity_component->createDuplicate();
      $entity_component->disableSave = TRUE;

      foreach ($this->configuration['field_override'] as $field_name => $override_field_values) {
        $entity_component->set($field_name, $override_field_values);
      }

    }

    $entity_view_builder = $this->entityHelper->getViewBuilder(DesignSystem::ENTITY_TYPE_ID_COMPONENT);

    $view_mode_id = 'default';
    if ($variant_id = $entity_component->get('variant')->target_id) {
      [$entity_type_id, $bundle_id, $view_mode_id] = explode('.', $variant_id);
    }

    if ($entity_component->bundle() == 'tabs') {
      $d = 1;
    }

    $build = $entity_view_builder->view($entity_component, $view_mode_id);

    // Form field_widget blocks need this built in order for form builder to
    // process all children.
    $build = $entity_view_builder->build($build);

    CacheableMetadata::createFromObject($this)->applyTo($build);

    $build['#pre_render'][] = [$this, 'preRenderComponentBlock'];
    return $build;
  }

  /**
   *
   */
  public function preRenderComponentBlock(array $element) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    return new TranslatableMarkup('todo');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $entity = $this->getEntity();

    return AccessResult::allowed();

    // First consult the entity.
    $access = $entity->access('view', $account, TRUE);

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'component' => NULL,
      'view_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $plugin_definition = $this->getPluginDefinition();

    $component_type_id = $plugin_definition['bundle'];

    $form['component'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => DesignSystem::ENTITY_TYPE_ID_COMPONENT,
      '#bundle' => $component_type_id,
      '#form_mode' => 'default',
      '#save_entity' => TRUE,
    ];

    if (!empty($config['component'])) {
      if ($entity_component = $this->designSystem->getComponent($config['component'])) {

        if (!empty($this->configuration['field_override'])) {

          $entity_component = $entity_component->createDuplicate();
          $entity_component->disableSave = TRUE;

          foreach ($this->configuration['field_override'] as $field_name => $override_field_values) {
            $entity_component->set($field_name, $override_field_values);
          }

        }

        $form['component']['#default_value'] = $entity_component;
      }
    }

    $form['#process'][] = [$this, 'processComponentBlockForm'];
    $form['#after_build'][] = [$this, 'afterBuildComponentBlockForm'];

    return $form;
  }

  /**
   * Process callback for component block form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array
   *   The processed form.
   */
  public function processComponentBlockForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    // Hide the default block form and use component entity fields for heading.
    $element['admin_label']['#access'] = FALSE;
    $element['label']['#access'] = FALSE;
    $element['label_display']['#access'] = FALSE;
    $element['label_display']['#default_value'] = FALSE;
    return $element;
  }

  /**
   * After build callback for component block form.
   *
   * @param array $element
   *   The form element.
   *
   * @return array
   *   The processed form.
   */
  public function afterBuildComponentBlockForm(array $element) {

    if (empty($element['component']['#entity'])) {
      return $element;
    }

    if (!empty($element['context_mapping']['entity']['#options']) && count($element['context_mapping']['entity']['#options']) > 1) {
      $element['context_mapping']['entity']['#value'] = 'layout_builder.entity';
      $element['context_mapping']['entity']['#default_value'] = 'layout_builder.entity';
      $element['context_mapping']['entity']['#wrapper_attributes']['class'][] = 'visually-hidden';
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $element['component']['#entity'];
    $entity_type_id = $entity->getEntityTypeId();

    if (in_array($entity_type_id, ['user', 'profile'])) {
      return $element;
    }

    // Component is always added in current user context. Any context dependent
    // blocks use a different context within context mapping of block field
    // formatter.
    if (FALSE && !empty($element['context_mapping']['entity']) && in_array('@user.current_user_context:current_user', array_keys($element['context_mapping']['entity']['#options']))) {
      $element['context_mapping']['entity']['#wrapper_attributes']['class'][] = 'visually-hidden';
      if (empty($element['context_mapping']['entity']['#default_value'])) {
        $element['context_mapping']['entity']['#default_value'] = '@user.current_user_context:current_user';
        $element['context_mapping']['entity']['#value'] = '@user.current_user_context:current_user';
      }
    }

    return $element;
  }

  /**
   * Adds the formatter third party settings forms.
   *
   * @param \Drupal\Core\Field\FormatterInterface $plugin
   *   The formatter.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $form
   *   The (entire) configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The formatter third party settings form.
   */
  protected function thirdPartySettingsForm(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $settings_form = [];
    // Invoke hook_field_formatter_third_party_settings_form(), keying resulting
    // subforms by module name.
    foreach ($this->moduleHandler->getImplementations('field_formatter_third_party_settings_form') as $module) {
      $settings_form[$module] = $this->moduleHandler->invoke($module, 'field_formatter_third_party_settings_form', [
        $plugin,
        $field_definition,
        EntityDisplayBase::CUSTOM_MODE,
        $form,
        $form_state,
      ]);
    }
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity_component */
    $entity_component = $form['settings']['component']['#entity'];

    if (!empty($this->configuration['field_override'])) {
      unset($this->configuration['field_override']);
    }

    // @todo why isn't IEF saving entity?
    $entity_component->save();

    $this->configuration['component'] = $entity_component->getRevisionId();

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [];
    return $cache_contexts;
  }

}
