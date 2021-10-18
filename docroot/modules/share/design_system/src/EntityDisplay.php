<?php

namespace Drupal\design_system;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\bd\Config\ProcessorInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Element;
use Drupal\design_system\Context\EntityDisplay as EntityDisplayContext;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\design_system\Ajax\Traits\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides entity display logic.
 */
class EntityDisplay implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use Form;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The config processor.
   *
   * @var \Drupal\bd\Config\ProcessorInterface
   */
  protected $configProcessor;

  /**
   * The factory to load a view executable with.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity display context.
   *
   * @var \Drupal\design_system\Context\EntityDisplay
   */
  protected $entityDisplayContext;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The default cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a EntityDisplay object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity storage for views.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The factory to load a view executable with.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache back end.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityHelper $entity_helper,
    DesignSystem $design_system,
    TypedConfigManagerInterface $typed_config_manager,
    ProcessorInterface $config_processor,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityDisplayContext $entity_display_context,
    RendererInterface $renderer,
    RouteMatchInterface $route_match,
    Connection $database,
    MessengerInterface $messenger,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->designSystem = $design_system;
    $this->typedConfigManager = $typed_config_manager;
    $this->configProcessor = $config_processor;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityDisplayContext = $entity_display_context;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.helper'),
      $container->get('design.system'),
      $container->get('config.typed'),
      $container->get('config.processor'),
      $container->get('entity_display.repository'),
      $container->get('design_system.context_provider.entity_display'),
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('database'),
      $container->get('messenger'),
      $container->get('cache.default'),
      $container->get('logger.channel.design_system')
    );
  }

  /**
   * Implements hook_field_widget_third_party_settings_form().
   *
   * @param \Drupal\Core\Field\WidgetInterface $plugin
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param $form_mode
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildFormConfigElementFieldWidget(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, $form, FormStateInterface $form_state) {
    $element = $this->buildFormConfigElementFieldGeneric('form', $plugin, $field_definition, $form_mode, $form, $form_state);
    return $element;
  }

  /**
   * Implements hook_field_formatter_third_party_settings_form().
   *
   * @param \Drupal\Core\Field\FormatterInterface $plugin
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param $view_mode
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildFormConfigElementFieldFormatter(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, $form, FormStateInterface $form_state) {
    $element = $this->buildFormConfigElementFieldGeneric('view', $plugin, $field_definition, $view_mode, $form, $form_state);
    return $element;
  }

  /**
   * Implements hook_field_formatter_third_party_settings_form().
   *
   * @param string $display_context
   * @param \Drupal\Core\Field\FormatterInterface $plugin
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param string $view_mode
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function buildFormConfigElementFieldGeneric($display_context, $plugin, FieldDefinitionInterface $field_definition, $mode, $form, FormStateInterface $form_state) {
    $config_schema_id = 'field_definition.third_party_settings';
    $settings = [];

    $field_name = $field_definition->getName();

    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      $entity = $form_object->getEntity();
      if ($entity instanceof EntityDisplayInterface) {
        $component_config = $entity->getComponent($field_name);
        $settings = !empty($component_config['third_party_settings']['design_system']) ? $component_config['third_party_settings']['design_system'] : [];
      }
    }
    elseif ($config = $form_state->get('design_system_third_party_settings')) {
      $settings = $config;
    }
    //
    //    $element = [
    //      '#type' => 'config_schema_subform',
    //      '#config_schema_id' => $config_schema_id,
    //      '#config_data' => $settings,
    //      '#is_new' => empty($settings),
    //    ];
    $element = [];

    if ($array_parents = $form_state->get('field_block_array_parents')) {
      $array_parents[] = 'design_system';
      $element['#parents'] = $array_parents;
      $element['#array_parents'] = $array_parents;
    }

    return $element;
  }

  /**
   * @param $view_mode
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityViewModeAlter(&$view_mode, EntityInterface $entity) {

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    if (!$entity->hasField('view_mode')) {
      return;
    }

    if (!$entity_view_mode = $entity->view_mode->entity) {
      return;
    }
    /** @var \Drupal\Core\Entity\EntityViewModeInterface $entity_view_mode */

    $entity_id_view_mode = $entity_view_mode->id();
    [$entity_type_id, $mode_id] = explode('.', $entity_id_view_mode);

    $view_mode = $mode_id;

  }

  /**
   * @param array $build
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   */
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {

    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();
    $entity_id = $entity->id();
    $view_mode_id = $display->getMode();

    $entity_type_id_skip = $this->designSystem->getConfigKey('entity.no_theme');
    if (in_array($entity_type_id, $entity_type_id_skip)) {
      return;
    }

    // Remove pre_render callback because we call it manually from
    // hook_entity_build_defaults_alter().
    if (!empty($build['#pre_render'])) {
      unset($build['#pre_render']);
    }

    $build['#theme'] = 'entity';
    $build['#entity_type_id'] = $entity_type_id;
    $build['#display'] = $display;
    $build["#{$entity_type_id}"] = $entity;
    $build['#entity'] = $entity;
    $build['#view_mode'] = $display->getMode();

    if (isset($build['_layout_builder']) && !Element::isEmpty($build['_layout_builder'])) {
      // This strips the contextual links from entity builds.
      // layout_builder_entity_view_alter($build, $entity, $display);.
    }

    $cache_skip_bundle_id = [
      'form',
    ];

    if (!in_array($bundle_id, $cache_skip_bundle_id)) {
      // Normalizer::recurseSetCache($build);
    }

    if (!empty($build['#contextual_links'][$entity_type_id])) {
      $build['#contextual_links'][$entity_type_id]['metadata']['entity_type'] = $entity_type_id;
      $build['#contextual_links'][$entity_type_id]['metadata']['entity_id'] = $entity_id;
      $build['#contextual_links'][$entity_type_id]['metadata']['view_mode'] = $view_mode_id;
    }

    $this->entityDisplayContext->addContext('view', $entity, $view_mode_id);

    $config_processors = [
      'callback_expand' => [
        'plugin_id' => 'callback_expand',
        'plugin_config' => [
          'callback_property' => '#pre_render',
        ],
      ],
      'entity_attribute' => [
        'plugin_id' => 'entity_attribute',
      ],
      'design_system_attribute' => [
        'plugin_id' => 'design_system_attribute',
      ],
      'container' => [
        'plugin_id' => 'container',
      ],
      'token_replacement' => [
        'plugin_id' => 'token_replacement',
      ],
    ];

    $map_context_config_processors = [
      'field_type' => [
        'context_value' => [
          'text',
          'text_long',
          'text_with_summary',
        ],
        'config_processor' => [
          'token_replacement',
        ],
      ],
    ];

    $static_cache = &drupal_static(__FUNCTION__, []);
    if (!isset($static_cache['is_expanding'])) {
      $static_cache['is_expanding'] = TRUE;
      $root_entity = TRUE;
    }
    else {
      $root_entity = FALSE;
    }

    $plugin_contexts = [];
    $plugin_contexts['entity'] = $entity;
    $plugin_contexts['view_mode_id'] = $view_mode_id;

    if ($section_storage = $this->routeMatch->getParameter('section_storage')) {
      if ($preview_storage = \Drupal::service('layout_builder.tempstore_repository')->get($section_storage)) {
        $section_storage = $preview_storage;
      }
      $plugin_contexts['section_storage'] = $section_storage;
    }

    foreach ($config_processors as $id => $config_processor) {
      $plugin_id = $config_processor['plugin_id'];
      $plugin_config = isset($config_processor['plugin_config']) ? $config_processor['plugin_config'] : [];
      $this->configProcessor->processArray($build, 'array_processor', $plugin_id, $plugin_config, $plugin_contexts);
    }

    if ($root_entity) {
      $static_cache['is_expanding'] = FALSE;
      $config_processors = [
        'layout_elements' => [
          'plugin_id' => 'layout_elements',
        ],
      ];

      if (!empty($entity->inLayoutBuilder)) {
        $config_processors['layout_builder_elements'] = [
          'plugin_id' => 'layout_builder_elements',
        ];
      }

      foreach ($config_processors as $id => $config_processor) {
        $plugin_id = $config_processor['plugin_id'];
        $plugin_config = isset($config_processor['plugin_config']) ? $config_processor['plugin_config'] : [];
        $this->configProcessor->processArray($build, 'array_processor', $plugin_id, $plugin_config, $plugin_contexts);
      }
    }

    $this->entityDisplayContext->removeContext('view', $entity, $view_mode_id);

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state) {

    $form['#attributes']['class'][] = 'form--entity';

    // @todo overlaps actions on entity form.
    if (!empty($form['content_translation'])) {
      $form['content_translation']['#attributes']['class'][] = 'visually-hidden';
    }

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    $form_id = $form_object->getFormId();
    if (in_array($form_id, ['view_edit_form', 'view_preview_form'])) {
      return;
    }

    $entity = $form_object->getEntity();

    $entity_type = $entity->getEntityType();
    $entity_op_id = $form_object->getOperation();
    $t_context = $this->entityHelper->getTContext($entity_type, $entity);

    $entity_type_display_config = $entity_type->get('display');

    if (!empty($entity_type_display_config['form']['op'][$entity_op_id])) {
      $form_config = $entity_type_display_config['form']['op'][$entity_op_id];
    }
    elseif (!empty($entity_type_display_config['form']['op']['*'])) {
      $form_config = $entity_type_display_config['form']['op']['*'];
    }
    else {
      $form_config = [];
    }

    if (!empty($form['actions'])) {
      $actions = &$form['actions'];
    }
    elseif (!empty($form['actions_workaround'])) {
      $actions = &$form['actions_workaround'];
    }

    $button_key = $entity->isNew() ? 'new' : 'existing';

    if (!empty($actions)) {

      $this->attachConfigAjax($form, $form_state);

      if (!empty($form['actions']['submit'])) {
        $form['actions']['submit']['#submit'][] = [static::class, 'entityFormSubmit'];
        $form['actions']['submit']['#validate'][] = [static::class, 'entityFormValidate'];
      }
      else {
        $form['#submit'][] = [static::class, 'entityFormSubmit'];
        $form['#validate'][] = [static::class, 'entityFormValidate'];
      }

      foreach (Element::children($actions) as $button_id) {

        $label_template = NULL;
        $button = &$actions[$button_id];

        $button_config = $form_config[$button_key]['button'][$button_id] ?? [];
        $button['#config'] = $button_config;

        $this->attachAjaxToElement($button, $form_state, $form);

        if ($entity->isNew()) {
          if (!empty($form_config['new']['button'][$button_id]['label_template'])) {
            $label_template = $form_config['new']['button'][$button_id]['label_template'];
          }
        }
        else {
          if (!empty($form_config['existing']['button'][$button_id]['label_template'])) {
            $label_template = $form_config['existing']['button'][$button_id]['label_template'];
          }
        }

        if (!empty($label_template)) {
          $button['#value'] = $this->t($label_template, $t_context);
        }
      }
    }

    if (!empty($form['actions']['delete'])) {
      unset($form['actions']['delete']);
    }

    if (!empty($form['revision'])) {
      $form['revision']['#access'] = FALSE;
    }

    $this->processEntityFieldWidgetConfig($form, $form_state);

    $make_container = [
      'url_redirects',
      'menu',
      'simple_sitemap',
    ];

    foreach ($make_container as $key) {
      if (!empty($form[$key])) {
        $form[$key]['#type'] = 'container';
      }
    }

    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $entity_form_display */
    if (!$entity_form_display = $form_state->get('form_display')) {
      return;
    }

    // Hide body summary field if set.
    if (!empty($form['body']['widget'][0]['summary'])) {
      $form['body']['widget'][0]['summary']['#access'] = FALSE;
    }

    if (!empty($form['advanced'])) {
      $form['advanced']['#type'] = 'container';
    }

    if (!empty($form['path']['widget'][0])) {
      // Put alias directly below publishing info.
      $form['path']['widget'][0]['#weight'] = -5;
    }

    // Confirm field_group module's #group properties are used after other
    // modules alter node elements to 'advanced' vertical tabs.
    if (!empty($form['#group_children'])) {

      foreach ($form['#group_children'] as $field_name => $group_name) {

        // Confirm children of group used #group key.
        if (!empty($form[$field_name])) {
          if (!empty($form[$field_name]['widget'][0]['#group'])) {
            $form[$field_name]['widget'][0]['#group'] = $group_name;
          }
          else {
            $form[$field_name]['#group'] = $group_name;
          }
        }

      }

    }

    if (!empty($form['path'])) {

      $form['path']['widget'][0]['#type'] = 'container';

      $path_config = $entity_form_display->getComponent('path');
      $form['path']['widget'][0]['#weight'] = $path_config['weight'];

      if (!empty($form['path']['widget'][0]['#group']) && $form['path']['widget'][0]['#group'] == 'advanced') {
        $form['path']['widget'][0]['#group'] = $path_config['region'];
      }

    }

    if (!empty($form['menu'])) {

      $menu_config = $entity_form_display->getComponent('menu');
      $menu_config['#weight'] = $menu_config['weight'];

      if ($form['menu']['#group'] == 'advanced') {
        $form['menu']['#group'] = $menu_config['region'];
      }
    }

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function entityFormValidate(array &$form, FormStateInterface $form_state) {

    $d = 1;

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    $entity = $form_object->getEntity();

    $config = [
      'name' => [
        'plugin' => 'form_state_values',
        'plugin_config' => [
          'selector' => 'mail.0.value',
        ],
      ],
    ];

    $values = $form_state->getValues();

    if ($entity->getEntityTypeId() == 'user') {
      $field_value = $values['mail'][0]['value'];
      foreach ($config as $field_name => $field_config) {
        $entity->set($field_name, $field_value);
      }
    }

    return $form_object->validateForm($form, $form_state);

  }

  /**
   * Submit handler for entity forms.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function entityFormSubmit(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $entity_type = $entity->getEntityType();

    $triggering_element = $form_state->getTriggeringElement();
    $config = $triggering_element['#config'];

    $messenger = \Drupal::messenger();
    if (!$messenger->messagesByType('status')) {
      $messenger->addStatus(t('Your changes to @entity_type_label_singular @entity_label were saved successfully.', [
        '@entity_type_label_singular' => $entity_type->getLabel(),
        '@entity_label' => $entity->label(),
      ]));
    }

    if (!empty($config['message']['pass']['list'])) {
      foreach ($config['message']['pass']['list'] as $key => $message) {
        $messenger->addStatus(t($message, [
          '@entity_type_label_singular' => $entity_type->getLabel(),
          '@entity_label' => $entity->label(),
        ]));
      }
    }
    else {
      $messenger->addStatus(t('Your changes to @entity_type_label_singular @entity_label were saved successfully.', [
        '@entity_type_label_singular' => $entity_type->getLabel(),
        '@entity_label' => $entity->label(),
      ]));
    }

  }

  /**
   * @param array $entity_form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function iefEntityFormAlter(array &$entity_form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $entity_form['#entity'];

    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();
    $form_mode_id = $entity_form['#form_mode'];

    // Determine entity form display ID.
    $entity_form_display_id = "{$entity_type_id}.{$bundle_id}.{$form_mode_id}";

    if ($entity_form_display = \Drupal::service('entity.helper')->getStorage('entity_form_display')->load($entity_form_display_id)) {

      $original_entity_form_display = $form_state->get('form_display');
      $form_state->set('form_display', $entity_form_display);

      $this->processEntityFieldWidgetConfig($entity_form, $form_state);

      $form_state->set('form_display', $original_entity_form_display);

    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function processEntityFieldWidgetConfig(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\field_layout\Entity\FieldLayoutEntityFormDisplay $entity_display */
    if (!$entity_display = $form_state->get('form_display')) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    if (!empty($form['#entity'])) {
      $entity = $form['#entity'];
    }
    else {
      $entity = $form_object->getEntity();
    }

    $third_party_settings = $entity_display->getThirdPartySettings('design_system') ?: [];
    if (!empty($third_party_settings['actions']['wrapper'])) {
      $this->designSystem->processConfigWrapper($form['actions'], $third_party_settings['actions']['wrapper']);
    }

    if (!empty($third_party_settings['actions']['button'])) {
      foreach ($third_party_settings['actions']['button'] as $button_id => $button_config) {
        if (empty($form['actions'][$button_id])) {
          continue;
        }
        $this->designSystem->processConfigButton($form['actions'][$button_id], $button_config);
      }
    }

    $entity_display_components = $entity_display->get('content');
    foreach ($entity_display_components as $key => &$item) {

      // Different field widget plugins have different structure of what needs
      // to be modified. So determine base level of component that should be
      // altered based on the entity field widget plugin.
      if (!empty($entity_display_components[$key]['type']) && $entity_display_components[$key]['type'] == 'text_textarea_with_summary') {
        $build_component = &$form[$key]['widget'][0];
      }
      elseif (!empty($form[$key]['widget'][0]['value'])) {
        $build_component = &$form[$key]['widget'][0]['value'];
      }
      elseif (!empty($form[$key])) {
        $build_component = &$form[$key];
      }
      else {
        continue;
      }

      $settings = &$item['third_party_settings']['design_system'];

      if ($key == 'mail') {
        $build_component['#attributes']['autofocus'] = TRUE;
      }

      if (!empty($settings['ajax']['behavior'])) {
        $build_component['#ajax'] = [
          'callback' => [static::class, 'ajaxOpBehavior'],
          'wrapper' => $form['#ajax_wrapper'],
          'event' => 'change',
        ];
      }

      if (!empty($settings['attribute']['class'])) {
        foreach ($settings['attribute']['class'] as $class) {
          if (empty($class)) {
            continue;
          }
          $build_component['#wrapper_attributes']['class'][] = $class;
        }
      }

      if (!empty($settings['access']['existing_only'])) {
        if ($entity->isNew()) {
          $build_component['#access'] = FALSE;
          $build_component['widget']['#access'] = FALSE;
          $build_component['widget'][0]['#access'] = FALSE;
          $build_component['widget'][0]['value']['#access'] = FALSE;
          continue;
        }
      }

      if (!empty($settings['element']['description'])) {
        if (!is_array($settings['element']['description'])) {
          $build_component['#description'] = t($settings['element']['description']);
          if (isset($build_component['widget']['#description'])) {
            $build_component['widget']['#description'] = $build_component['#description'];
          }
          if (isset($build_component['widget']['current']['#prefix'])) {
            $build_component['widget']['current']['#prefix'] = "<p>{$build_component['#description']}</p>";
          }
        }
      }

      if (!empty($settings['element']['label'])) {
        if (!is_array($settings['element']['label'])) {
          $build_component['#title'] = t($settings['element']['label']);
          if (isset($build_component['widget']['#title'])) {
            $build_component['widget']['#title'] = $build_component['#title'];
          }
          if (isset($build_component['widget']['label']['#title'])) {
            $build_component['widget']['label']['#title'] = $build_component['#title'];
          }
        }
      }

      if (!empty($settings['element']['prefix'])) {
        $build_component['#prefix'] = t($settings['element']['prefix']);
      }

      if (!empty($settings['element']['suffix'])) {
        $build_component['#suffix'] = t($settings['element']['suffix']);
      }

      if (!empty($settings['element']['title_display'])) {
        $build_component['#title_display'] = $settings['element']['title_display'];
      }

      if (!empty($settings['element']['description_display'])) {
        $build_component['#description_display'] = $settings['element']['description_display'];

        // @todo container has description element in some cases.
        if ($build_component['#description_display'] == 'invisible') {
          if (!empty($build_component['widget']['description'])) {
            unset($build_component['widget']['description']);
          }
        }
      }

    }

  }

}
