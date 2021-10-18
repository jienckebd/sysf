<?php

namespace Drupal\design_system;

use Drupal\Core\Render\Element;
use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Render\RendererInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\EntityInterface;
use Drupal\design_system\Plugin\Layout\LayoutEntity;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

/**
 * Provides design system management.
 */
class DesignSystem {

  use StringTranslationTrait;

  /**
   * The component entity type ID.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_COMPONENT = 'block_content';

  /**
   * The DOM entity type ID.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_DOM = 'dom';

  /**
   * The component type entity type ID.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_COMPONENT_TYPE = 'block_content_type';

  /**
   * @var string
   */
  const CONFIG_ID = 'design_system.settings';

  /**
   * Field name used to store layout builder layout.
   */
  const FIELD_NAME_LAYOUT_BUILDER_LAYOUT = 'layout_builder__layout';

  /**
   * Field name used to store entity references to components.
   */
  const FIELD_NAME_LAYOUT_BUILDER_COMPONENT = 'field_layout_builder__component';

  /**
   * Field name for default component.
   *
   * @var string
   */
  const FIELD_NAME_DEFAULT_COMPONENT = 'field_default_entity';

  /**
   * Field name for default component.
   *
   * @var string
   */
  const FIELD_NAME_LAYOUT_ROW = 'field_cmp_layout_row';

  /**
   * Field name for default component.
   *
   * @var string
   */
  const FIELD_NAME_REGION = 'field_cmp_region';

  /**
   * Field name for components or children components.
   *
   * @var string
   */
  const FIELD_NAME_COMPONENT = 'field_component';

  /**
   * Field name for container.
   *
   * @var string
   */
  const FIELD_NAME_CONTAINER = 'container';

  /**
   * The default layout plugin ID.
   *
   * @var string
   */
  const DEFAULT_LAYOUT_ID = 'create_layout_entity';

  /**
   * The entity ID of default component tag.
   *
   * @var int
   */
  const ENTITY_ID_COMPONENT_TAG_DEFAULT = 110;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The factory to load a view executable with.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The route object.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $routeObject;

  /**
   * The route provider to get route by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The entity from route or NULL.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entityFromRoute;

  /**
   * The component storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface
   */
  protected $entityStorageComponent;

  /**
   * The entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityViewBuilderComponent;

  /**
   * Constructs a DesignSystem object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity storage for views.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The factory to load a view executable with.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityDisplayRepositoryInterface $entity_display_repository,
    RendererInterface $renderer,
    LayoutPluginManagerInterface $layout_plugin_manager,
    Token $token,
    ContextRepositoryInterface $context_repository,
    RouteMatchInterface $route_match,
    RouteProviderInterface $route_provider,
    Connection $database,
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->renderer = $renderer;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->token = $token;
    $this->contextRepository = $context_repository;
    $this->routeMatch = $route_match;
    $this->routeProvider = $route_provider;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->logger = $logger;
    $this->routeObject = $this->routeMatch->getRouteObject();
    $this->routeName = $this->routeMatch->getRouteName();
    $this->entityStorageComponent = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_COMPONENT);
    $this->entityViewBuilderComponent = $this->entityHelper->getViewBuilder(static::ENTITY_TYPE_ID_COMPONENT);
  }

  /**
   *
   */
  public function processConfigHeading() {
  }

  /**
   *
   */
  public function processConfigWrapper() {
  }

  /**
   *
   */
  public function processConfigCollapse() {
  }

  /**
   *
   */
  public function processConfigButton() {
  }

  /**
   * {@inheritDoc}
   */
  public function buildPluginDefinition($plugin_type, $entity_type_id, $bundle_id, array $mapping = []) {

    $return = [];

    $entity_storage = $this->entityHelper->getStorage($entity_type_id);
    $entity_type = $this->entityHelper->getDefinition($entity_type_id);

    $entity_key_bundle = $entity_type->getKey('bundle');

    $load_properties = [
      $entity_key_bundle => $bundle_id,
    ];

    if (!$entities = $entity_storage->loadByProperties($load_properties)) {
      return FALSE;
    }

    foreach ($entities as $entity_id => $entity) {

      $layout_config = $this->getLayoutEntityConfig($entity);

      if (empty($layout_config['row'])) {
        continue;
      }

      $label = $entity->label();
      $uuid = $entity->uuid();

      $plugin_id = "design_system__{$uuid}";

      $category = "Standard";
      $tags = [];

      $module_path = drupal_get_path('module', 'design_system');
      $layout = [];
      $layout['additional']['entity_id'] = $entity->id();
      $layout['additional']['revision_id'] = $entity->getRevisionId();
      $layout['label'] = $label;
      $layout['template'] = 'standard-layout';
      $layout['path'] = "{$module_path}/templates";
      $layout['templatePath'] = $layout['path'];
      $layout['theme_hook'] = "layout__{$plugin_id}";
      $layout['class'] = LayoutEntity::class;
      $layout['provider'] = 'design_system';
      $layout['id'] = $plugin_id;
      $layout['category'] = $category;

      $layout['icon_map'] = [];
      $layout['tag'] = $tags;

      // Regions are defined in dynamic regions callback but need to be parsed
      // in order to build layout icon.
      $layout['regions'] = [];

      foreach ($layout_config['row'] as $row_id => $row_config) {
        $cols_in_row = [];
        foreach ($row_config['region'] as $region_id => $region_config) {
          $layout['regions'][$region_id] = [
            'label' => $region_config['label'],
          ];
          $cols_in_row[] = $region_id;
        }
        $layout['icon_map'][] = $cols_in_row;
      }

      $layout['default_region'] = $layout_config['default_region'];
      $return[$plugin_id] = new LayoutDefinition($layout);

    }

    return $return;
  }

  /**
   * @param \Drupal\block_content\BlockContentInterface $entity
   *
   * @return array
   */
  public function getLayoutEntityConfig(BlockContentInterface $entity) {
    $config = [];
    $row = 1;

    $default_region = NULL;

    foreach ($entity->get(static::FIELD_NAME_LAYOUT_ROW) as $delta_row => $field_item_row) {

      /** @var \Drupal\block_content\BlockContentInterface $entity_layout_row */
      $entity_layout_row = $field_item_row->entity;

      if (empty($entity_layout_row)) {
        $this->logger->warning("Invalid layout: @entity_id", [
          '@entity_id' => $entity->id(),
        ]);
        continue;
      }

      $entity_id_row = $entity_layout_row->id();

      $first_row = empty($first_row) ? $entity_id_row : $first_row;

      $config['row'][$entity_id_row] = [];
      $config['row'][$entity_id_row]['label'] = $entity_layout_row->label();
      $config['row'][$entity_id_row]['region'] = [];
      $config['row'][$entity_id_row]['entity_id'] = $entity_id_row;
      $config['row'][$entity_id_row]['revision_id'] = $entity_layout_row->getRevisionId();
      $config['row'][$entity_id_row]['delta'] = $delta_row;

      $col = 1;

      foreach ($entity_layout_row->get(static::FIELD_NAME_REGION) as $delta_region => $field_item_region) {

        /** @var \Drupal\block_content\BlockContentInterface $entity_region */
        $entity_region = $field_item_region->entity;

        $entity_id_region = $entity_region->id();

        $region_id = "layout_entity__{$entity_id_row}__{$entity_id_region}";

        $default_region = empty($default_region) ? $region_id : $default_region;

        if ($entity_region->field_default->value) {
          $config['default_region'] = $region_id;
        }

        $config['row'][$entity_id_row]['region'][$region_id]['label'] = $entity_region->label();
        $config['row'][$entity_id_row]['region'][$region_id]['entity_id'] = $entity_id_region;
        $config['row'][$entity_id_row]['region'][$region_id]['revision_id'] = $entity_region->getRevisionId();
        $config['row'][$entity_id_row]['region'][$region_id]['delta'] = $delta_region;

        $col++;
      }

      $row++;
    }

    if (empty($config['default_region'])) {
      $config['default_region'] = $default_region;
    }

    return $config;
  }

  /**
   *
   */
  public function buildBaseLayoutEntity() {

    $entity_storage_component = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_COMPONENT);

    $total = 12;
    $count = 1;
    while ($count <= $total) {

      $base_layout_label = "Base: Row 1 / Column {$count}";

      $layout_entity_properties = [
        'type' => 'layout',
        'info' => $base_layout_label,
        'reusable' => TRUE,
      ];

      if ($count == 1) {
        $layout_entity_properties['field_default'] = TRUE;
      }

      /** @var \Drupal\block_content\BlockContentInterface $entity_layout */
      if ($entity_existing = $entity_storage_component->loadByProperties($layout_entity_properties)) {
        $entity_layout = reset($entity_existing);
      }
      else {
        $entity_layout = $entity_storage_component->create($layout_entity_properties);
      }

      // Delete existing.
      foreach ($entity_layout->get(static::FIELD_NAME_LAYOUT_ROW) as $delta_row => $field_items_layout_row) {
        if ($entity_layout_row = $field_items_layout_row->entity) {
          $entity_layout_row->delete();
        }

      }
      $entity_layout->set(static::FIELD_NAME_LAYOUT_ROW, []);

      foreach ($entity_layout->get(static::FIELD_NAME_CONTAINER) as $delta_row => $field_items_container) {
        if ($entity_container = $field_items_container->entity) {
          $entity_container->delete();
        }
      }
      $entity_layout->set(static::FIELD_NAME_CONTAINER, []);

      /** @var \Drupal\block_content\BlockContentInterface $entity_layout_container */
      $entity_layout_container = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_COMPONENT)
        ->create([
          'type' => 'container',
        ]);

      $entity_layout_container_style = $this->entityHelper->getStorage(self::ENTITY_TYPE_ID_DOM)
        ->create([
          'bundle' => 'style',
        ]);
      $entity_layout_container_style->set('display', 'flex');

      $entity_layout_container_nested_style = $this->entityHelper->getStorage(self::ENTITY_TYPE_ID_DOM)
        ->create([
          'bundle' => 'style',
        ]);

      $entity_layout_container_nested_style->set('dom', $this->getDomEntityBySelector('> .region')->id());
      $entity_layout_container_nested_style->set('display', 'block');
      $entity_layout_container_nested_style->set('width', '100%');
      $entity_layout_container_nested_style->save();

      $entity_layout_container_style->set('field_style', $entity_layout_container_nested_style->id());
      $entity_layout_container_style->save();

      $entity_layout_container->set('field_dom_style', $entity_layout_container_style->id());
      $entity_layout_container->save();

      $entity_layout->get('container')->appendItem([
        'target_id' => $entity_layout_container->id(),
        'target_revision_id' => $entity_layout_container->getRevisionId(),
      ]);

      $rows = 1;
      $row_count = 1;
      $layout_row_values = [];
      while ($row_count <= $rows) {

        $entity_layout_row = $entity_storage_component->create([
          'type' => 'layout_row',
          'info' => "Row {$row_count}",
        ]);

        $region_count = 1;
        $region_values = [];
        while ($region_count <= $count) {

          $region_label = "Row 1 / Column {$region_count}";
          $region_entity_properties = [
            'type' => 'region',
            'info' => $region_label,
          ];

          if ($region_count == 1) {
            $region_entity_properties['field_default'] = TRUE;
          }

          $entity_region = $entity_storage_component->create($region_entity_properties);
          $entity_region->save();
          $region_values[] = [
            'entity' => $entity_region,
            'target_id' => $entity_region->id(),
            'target_revision_id' => $entity_region->getRevisionId(),
            'target_uuid' => $entity_region->uuid(),
          ];

          $region_count++;
        }

        $entity_layout_row->set(static::FIELD_NAME_REGION, $region_values);
        $entity_layout_row->save();

        $layout_row_values[] = [
          'entity' => $entity_layout_row,
          'target_id' => $entity_layout_row->id(),
          'target_revision_id' => $entity_layout_row->getRevisionId(),
          'target_uuid' => $entity_layout_row->uuid(),
        ];

        $row_count++;
      }

      $entity_layout->set('tags', static::ENTITY_ID_COMPONENT_TAG_DEFAULT);

      $entity_layout->set(static::FIELD_NAME_LAYOUT_ROW, $layout_row_values);
      $entity_layout->save();

      $count++;
    }
  }

  /**
   * @param $selector
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getDomEntityBySelector($selector) {

    $entity_values = [
      'bundle' => 'selector',
      'label' => $selector,
    ];

    if ($existing = $this->entityHelper->getStorage(self::ENTITY_TYPE_ID_DOM)->loadByProperties($entity_values)) {
      $entity = reset($existing);
    }
    else {
      $entity = $this->entityHelper->getStorage(self::ENTITY_TYPE_ID_DOM)->create($entity_values);
      $entity->save();
    }

    return $entity;
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultComponent($component_type) {
    /** @var \Drupal\bd\Entity\Entity\Content $component_type_wrapper */
    $component_type_wrapper = $this->getComponentTypeWrapper($component_type);
    if ($default_entity = $component_type_wrapper->get(static::FIELD_NAME_DEFAULT_COMPONENT)->entity) {
      return $default_entity;
    }

    // Create a new one and assign as default.
    $new_default_component = $this->entityStorageComponent->create([
      'type' => $component_type_wrapper->subject->target_id,
      'label' => 'Default',
    ]);
    $new_default_component->save();
    $component_type_wrapper->set(static::FIELD_NAME_DEFAULT_COMPONENT, [
      'entity' => $new_default_component,
      'target_id' => $new_default_component->id(),
      'target_revision_id' => $new_default_component->getRevisionId(),
      'target_uuid' => $new_default_component->uuid(),
    ])
      ->save();

    return $new_default_component;
  }

  /**
   * {@inheritDoc}
   */
  public function getComponentType($component_type) {
    if (is_string($component_type)) {
      $component_type = $this->entityHelper
        ->getStorage(static::ENTITY_TYPE_ID_COMPONENT_TYPE)
        ->load($component_type);
    }
    return $component_type;
  }

  /**
   * {@inheritDoc}
   */
  public function getComponentTypeWrapper($component_type) {
    $component_type = $this->getComponentType($component_type);

    /** @var \Drupal\bd\Config\Wrapper\Manager $config_entity_wrapper_manager */
    $config_entity_wrapper_manager = \Drupal::service('config_entity_wrapper.manager');

    return $config_entity_wrapper_manager->getWrapperForEntity($component_type);
  }

  /**
   * {@inheritDoc}
   */
  public function getComponent($component_revision_id) {
    $component = $this->entityStorageComponent->loadRevision($component_revision_id);
    if (empty($component)) {
      return FALSE;
    }
    return $component;
  }

  /**
   * {@inheritDoc}
   */
  public function viewComponent($component, $view_mode_id = 'default') {

    if (is_int($component) || is_string($component)) {
      if (!$component = $this->getComponent($component)) {
        return FALSE;
      }
    }

    $build = $this->entityViewBuilderComponent->view($component, $view_mode_id);
    return $build;
  }

  /**
   * Expand all entity builds at once.
   *
   * @param array $build
   */
  protected function recurseExpandComponentBuild(array &$build) {

    foreach (Element::children($build) as $child_key) {

      $child = &$build[$child_key];
      if (!is_array($child)) {
        continue;
      }

      if (isset($child['#component']) && isset($child['#theme']) && ($child['#theme'] == 'component')) {
        $child = $this->entityViewBuilderComponent->build($child);
      }

      $this->recurseExpandComponentBuild($child);
    }

  }

  /**
   * {@inheritDoc}
   */
  public function getOptionComponentType() {

    $options_component_type = [];
    foreach ($this->entityHelper->getStorage(static::ENTITY_TYPE_ID_COMPONENT_TYPE)->loadMultiple() as $component_type) {

      $component_type_id = $component_type->id();
      $component_type_label = $component_type->label();

      $options_component_type[$component_type_id] = $component_type_label;

    }

    return $options_component_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionComponentTypeViewMode($component_type_id) {
    $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle(static::ENTITY_TYPE_ID_COMPONENT, $component_type_id);
    return $view_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($option_name) {

    $cache_static = &drupal_static(__FUNCTION__);
    if (empty($cache_static[$option_name])) {

      if (!$config_value = $this->getConfigKey($option_name)) {
        $cache_static[$option_name] = [];
        return $cache_static[$option_name];
      }

      foreach ($config_value as $key => $value) {

        $data_points = substr_count($value, '||');

        if ($data_points == 2) {
          [$key_use, $label, $group] = explode('||', $value);
          $cache_static[$option_name][$group][$key_use] = $key_use;
        }
        elseif ($data_points == 1) {
          [$key_use, $label_use] = explode('||', $value);
          $cache_static[$option_name][$key_use] = $label_use;
        }
        else {
          $cache_static[$option_name][$value] = $value;
        }

      }

    }

    return $cache_static[$option_name];
  }

  /**
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *
   * @return array
   */
  public static function getOptionGeneric(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL) {
    $service = \Drupal::service('design.system');

    $field_name = $definition->getName();

    $map_field_name_config_key = [
      'field_button_type' => 'button.type',
      'field_button_size' => 'button.size',
      'field_icon_position' => 'button.icon_position',
      'class_container' => 'class.wrapper',
      'field_class_container' => 'class.wrapper',
      'class_inner' => 'class.wrapper',
      'field_class' => 'class.wrapper',
      'container' => 'space.container',
      'field_dialog_class' => 'class.wrapper',
      'field_tag' => 'tag.wrapper',
      'wrapper_tag' => 'tag.wrapper',
      'link_type' => 'link.type',
      'field_toggle_type' => 'link.toggle',
      'field_alert_type' => 'alert.type',
      'field_link_type' => 'link.type',
      'field_tooltip_type' => 'link.tooltip',
    ];

    if (empty($map_field_name_config_key[$field_name])) {
      return [];
    }

    $option_config_key = $map_field_name_config_key[$field_name];

    return $service->getOption($option_config_key);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigKey($key) {

    $config = $this->configFactory->get(static::CONFIG_ID);
    $value = $config->get($key);

    if (stripos($value, "\r\n") !== FALSE) {
      $value = explode("\r\n", $value);
    }
    else {
      // Always return an array.
      $value = [$value];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawConfigKey($key) {

    $config = $this->configFactory->get(static::CONFIG_ID);
    $value = $config->get($key);

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTokenReplace($string, EntityInterface $entity = NULL) {
    if ($this->token->scan($string)) {
      $token_info = [];

      if (!empty($_ENV['SYS_ENTITY_RENDER'])) {
        $token_info[$_ENV['SYS_ENTITY_RENDER']->getEntityTypeId()] = $_ENV['SYS_ENTITY_RENDER'];
      }

      // @todo token.
      if (!empty($entity)) {
        $token_info[$entity->getEntityTypeId()] = $entity;
      }

      $string = $this->token->replace($string, $token_info, [
        'clear' => TRUE,
      ]);

      if ((stripos($string, 'http://') !== 0) && (stripos($string, 'https://') !== 0)) {
        $string = Url::fromUri("internal:{$string}");
        $string = $string->toString();
      }

    }
    return $string;
  }

  /**
   * {@inheritdoc}
   */
  public function processConfigAos(&$attributes, array &$settings) {

    $attributes['data-aos'] = $settings['animation']['animation'];

    if (!empty($settings['animation']['duration'])) {
      $attributes['data-aos-duration'] = $settings['animation']['duration'];
    }

    if (!empty($settings['animation']['delay'])) {
      $attributes['data-aos-delay'] = $settings['animation']['delay'];
    }

    if (!empty($settings['animation']['offset'])) {
      $attributes['data-aos-offset'] = $settings['animation']['offset'];
    }

    if (!empty($settings['animation']['anchor_placement'])) {
      $attributes['data-aos-anchor-placement'] = $settings['animation']['anchor_placement'];
    }

    if (!empty($settings['animation']['anchor'])) {
      $attributes['data-aos-anchor'] = $settings['animation']['anchor'];
    }

    if (!empty($settings['animation']['mirror'])) {
      $attributes['data-aos-mirror'] = $settings['animation']['mirror'];
    }

    if (!empty($settings['animation']['once'])) {
      $attributes['data-aos-once'] = $settings['animation']['once'];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getRouteIcon($route_name = NULL) {

    $icon = FALSE;
    $route_name_icon_config = $this->getOption('icon.route_name');
    $entity_type_icon_config = $entity_type_icon_config = $this->getOption('icon.entity_type');

    if (empty($route_name_icon_config) && empty($entity_type_icon_config)) {
      return $icon;
    }

    if (empty($route_name)) {
      $route_name = $this->routeName;
      $route_object = $this->routeObject;
    }
    else {
      $route_object = $this->routeProvider->getRouteByName($route_name);
    }

    if (!empty($route_name_icon_config[$route_name])) {
      $icon = $route_name_icon_config[$route_name];
    }
    else {
      if ($route_entity_type_id = $route_object->getOption('_entity_type_id')) {
        if (!empty($entity_type_icon_config[$route_entity_type_id])) {
          $icon = $entity_type_icon_config[$route_entity_type_id];
        }
      }
    }

    return $icon;
  }

  /**
   * {@inheritDoc}
   */
  public function getEntityFromRoute() {
    /** @var \Drupal\Core\Entity\EntityInterface|null $entity_from_route */
    if (!$this->entityFromRoute) {
      if ($route_param_list = $this->routeMatch->getParameters()) {
        foreach ($route_param_list as $route_param_id => $route_param_value) {
          if ($route_param_value instanceof EntityInterface) {
            $this->entityFromRoute = $route_param_value;
            break;
          }
        }
      }
    }
    return $this->entityFromRoute;
  }

  /**
   * {@inheritDoc}
   */
  public function getDisplayContext($context_type) {

    $context_ids = [
      "@design_system.context_provider.entity_display:display.{$context_type}.entity",
      "@design_system.context_provider.entity_display:display.{$context_type}.mode",
      "@design_system.context_provider.entity_display:display.{$context_type}.display",
    ];

    if (!$contexts = $this->contextRepository->getRuntimeContexts($context_ids)) {
      return NULL;
    }

    $return = [];
    $return['entity'] = $contexts["@design_system.context_provider.entity_display:display.{$context_type}.entity"]->getContextValue();
    $return['mode'] = $contexts["@design_system.context_provider.entity_display:display.{$context_type}.mode"]->getContextValue();
    $return['display'] = $contexts["@design_system.context_provider.entity_display:display.{$context_type}.display"]->getContextValue();

    return $return;

  }

}
