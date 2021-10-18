<?php

namespace Drupal\design_system;

use Drupal\Core\Entity\EntityFormModeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\layout_builder\Section;

/**
 * Build and manage entity displays.
 */
class EntityDisplayBuilder {

  use StringTranslationTrait;

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
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

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
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

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
   * Constructs a EntityDisplayBuilder object.
   *
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity storage for views.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The factory to load a view executable with.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
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
    DesignSystem $design_system,
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityDisplayRepositoryInterface $entity_display_repository,
    RendererInterface $renderer,
    Token $token,
    Connection $database,
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->designSystem = $design_system;
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->renderer = $renderer;
    $this->token = $token;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncFormModeViewModes() {

    $entity_storage_entity_form_mode = $this->entityHelper->getStorage('entity_form_mode');

    $entities_form_mode = $entity_storage_entity_form_mode->loadMultiple();

    foreach ($this->entityHelper->getDefinitionsByTag('display') as $entity_type_id => $entity_type) {

      $default_mode_id = "form__default";
      $entity_id_view_mode = "{$entity_type_id}.{$default_mode_id}";
      $entity_label_view_mode = $this->t('Form: Default');

      $this->createViewMode($entity_type_id, $entity_id_view_mode, $entity_label_view_mode);
    }

    /** @var \Drupal\Core\Entity\EntityFormModeInterface $entity_form_mode */
    foreach ($entities_form_mode as $entity_form_mode) {
      $this->createViewModeForFormMode($entity_form_mode);
    }

  }

  /**
   * @param \Drupal\Core\Entity\EntityFormModeInterface $entity_form_mode
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createViewModeForFormMode(EntityFormModeInterface $entity_form_mode) {

    // Create an entity view mode for this form mode.
    $entity_id_form_mode = $entity_form_mode->id();
    $target_entity_type_id = $entity_form_mode->getTargetType();

    $mode_id = str_replace("{$target_entity_type_id}.", "", $entity_id_form_mode);

    $entity_id_view_mode = "{$target_entity_type_id}.form__{$mode_id}";

    $entity_label_view_mode = $this->t('Form: @entity_label', [
      '@entity_label' => $entity_form_mode->label(),
    ]);

    $this->createViewMode($target_entity_type_id, $entity_id_view_mode, $entity_label_view_mode);

    // Confirm each bundle has the form__default view display.
    $this->buildAllDisplayForEntityType($target_entity_type_id, 'form', "form__default");

  }

  /**
   * @param $target_entity_type_id
   * @param $entity_id_view_mode
   * @param $entity_label_view_mode
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createViewMode($target_entity_type_id, $entity_id_view_mode, $entity_label_view_mode) {

    $entity_storage_entity_view_mode = $this->entityHelper->getStorage('entity_view_mode');

    if ($entity_view_mode = $entity_storage_entity_view_mode->load($entity_id_view_mode)) {
      return FALSE;
    }

    $entity_values_entity_view_mode = [
      'id' => $entity_id_view_mode,
      'label' => $entity_label_view_mode,
      'targetEntityType' => $target_entity_type_id,
      'status' => TRUE,
      'cache' => TRUE,
    ];
    $entity_view_mode = $entity_storage_entity_view_mode->create($entity_values_entity_view_mode);
    $entity_view_mode->save();

    // @todo entity dependency to delete view mode when form mode deleted.
  }

  /**
   * @param $target_entity_type_id
   * @param $display_context_id
   * @param $mode_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildAllDisplayForEntityType($target_entity_type_id, $display_context_id, $mode_id) {
    foreach ($this->entityTypeBundleInfo->getBundleInfo($target_entity_type_id) as $bundle_id => $bundle_info) {
      $this->buildDisplayTemplate($target_entity_type_id, $bundle_id, $display_context_id, $mode_id);
    }
  }

  /**
   * @param $target_entity_type_id
   * @param $bundle_id
   * @param string $display_context_id
   * @param string $mode_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildDisplayTemplate($target_entity_type_id, $bundle_id, $display_context_id = 'form', $mode_id = 'form__default', $template_id = 'default') {

    $default_form_config = [
      'entity_field_group' => [
        'overview' => [
          'label' => 'Overview',
          'children' => [
            'entity_key' => [
              'label',
            ],
          ],
        ],
        'advanced' => [
          'label' => 'Advanced',
        ],
        'publishing' => [
          'label' => 'Publishing',
        ],
      ],
    ];

    $field_config = $this->configFactory->get('bd.entity.field.common');
    $display_config_disable = $field_config->get('display_configurable_disable') ?: [];
    $entity_storage_entity_view_display = $this->entityHelper->getStorage('entity_view_display');
    $template_config = $this->entityHelper->getBundleConfig($target_entity_type_id, $bundle_id, "display.{$display_context_id}.template.{$template_id}");

    $entity_id_entity_view_display = "{$target_entity_type_id}.{$bundle_id}.{$mode_id}";

    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $entity_view_display */
    if (!$entity_view_display = $entity_storage_entity_view_display->load($entity_id_entity_view_display)) {
      $entity_view_display = $entity_storage_entity_view_display->create([
        'id' => $entity_id_entity_view_display,
        'targetEntityType' => $target_entity_type_id,
        'bundle' => $bundle_id,
        'mode' => $mode_id,
        'status' => TRUE,
      ]);
    }

    // Don't run for layout builder displays.
    if ($entity_view_display->getThirdPartySetting('layout_builder', 'enabled') === FALSE) {
      return;
    }

    // Reset any current sections.
    $entity_view_display->removeAllSections();

    $entity_view_display->appendSection(new Section(DesignSystem::DEFAULT_LAYOUT_ID));
    $section = $entity_view_display->getSection(0);
    $region = $section->getDefaultRegion();

    foreach ($this->entityFieldManager->getFieldDefinitions($target_entity_type_id, $bundle_id) as $field_name => $field_definition) {

      // This only works for base fields.
      if (!$field_definition->isDisplayConfigurable($display_context_id)) {
        continue;
      }

      if ($field_definition instanceof FieldConfigInterface) {
        $field_definition_type = 'field_config';
      }
      else {
        $field_definition_type = 'base_field_definition';
      }

      if (!empty($template_config['field_definition_types'])) {
        if (!in_array($field_definition_type, $template_config['field_definition_types'])) {
          continue;
        }
      }

      // This applies to all fields.
      if (!empty($display_config_disable[$display_context_id]['field_name']) && in_array($field_name, $display_config_disable[$display_context_id]['field_name'])) {
        continue;
      }

      $options = [];

      $entity_view_display->setComponent($field_name, $options);

    }

    $extra_fields = $this->entityFieldManager->getExtraFields($target_entity_type_id, $bundle_id);
    if (!empty($extra_fields[$display_context_id])) {
      foreach ($extra_fields[$display_context_id] as $extra_field_name => $extra_field_definition) {
        $entity_view_display->setComponent($extra_field_name, $extra_field_definition);
      }
    }

    $entity_view_display->setThirdPartySetting('layout_builder', 'enabled', TRUE);

    // @todo entity dependency.
    $entity_view_display->set('status', TRUE);
    $entity_view_display->set('langcode', 'en');
    $entity_view_display->save();
  }

  /**
   *
   */
  public function getDependencies($entity_type_id, $bundle_id, $display_context, $mode_id) {

  }

  /**
   *
   */
  public function addFieldDefinitionsToDisplay() {
  }

  /**
   *
   */
  public function addExtraFieldsToDisplay() {
  }

  /**
   *
   */
  public function addFieldsToDisplay() {
    $children = [
      'label_ia',
      'label_browser',
    ];

    $field_group = [
      'format_type' => 'details',
      'label' => 'Alternate titles',
      'children' => [],
      'parent_name' => NULL,
      'format_settings' => [
        'description' => '',
        'open' => TRUE,
        'required_fields' => TRUE,
        'id' => '',
        'classes' => 'mb-4',
      ],
      'weight' => 100,
    ];

    $this->bulkUpdateDisplay('node', ['landing_page'], 'form', 'row1_col1', 'group_meta', $children, 'alternate_title', $field_group);

    // $this->bulkUpdateDisplay('taxonomy_term', [], 'form', 'row1_col1', 'group_advanced', $children, 'alternate_title', $field_group);
  }

  /**
   * @param $entity_type_id
   * @param array $bundles
   * @param $display_context_id
   * @param $region_id
   * @param $field_group_parent
   * @param $field_group_children
   * @param null $new_field_group_name
   * @param array $new_field_group
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function bulkUpdateDisplay($entity_type_id, array $bundles, $display_context_id, $region_id, $field_group_parent, $field_group_children, $new_field_group_name = NULL, $new_field_group = []) {

    $entity_type_id_entity_display = ($display_context_id == 'form') ? 'entity_form_display' : 'entity_view_display';

    $entity_storage_entity_display = $this->entityHelper->getStorage($entity_type_id_entity_display);
    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    $label_key = $entity_type->getKey('label');

    $entities = [];

    if (!empty($bundles)) {
      foreach ($bundles as $bundle_id) {
        if (!$displays = $entity_storage_entity_display->loadByProperties(['targetEntityType' => $entity_type_id, 'bundle' => $bundle_id])) {
          continue;
        }
        foreach ($displays as $display) {
          $entities[$display->id()] = $display;
        }
      }
    }
    else {
      $displays = $entity_storage_entity_display->loadByProperties(['targetEntityType' => $entity_type_id]);
      foreach ($displays as $display) {
        $entities[$display->id()] = $display;
      }
    }

    if (empty($entities)) {
      return;
    }

    if (!empty($new_field_group)) {
      $new_field_group['parent_name'] = $field_group_parent;
      $new_field_group['children'] = $field_group_children;
      $new_field_group['region'] = $region_id;
    }

    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $entity */
    foreach ($entities as $entity) {

      $content = $entity->get('content');
      $third_party_settings = $entity->get('third_party_settings');

      $third_party_settings['field_group'][$new_field_group_name] = $new_field_group;

      if (!empty($third_party_settings['field_group'][$field_group_parent])) {
        $third_party_settings['field_group'][$field_group_parent]['children'][] = $new_field_group_name;
      }

      $field_config = $entity->getComponent($label_key);

      foreach ($field_group_children as $child_id) {
        $entity->setComponent($child_id, $field_config);
      }

      $entity->set('third_party_settings', $third_party_settings);

      $entity->save();

    }

  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildImageStyleViewModeAll() {

    $map_image_style_view_mode_default = [
      'nw' => [
        'label' => 'Narrow wide',
        'ratio' => [
          'x' => 5,
          'y' => 1,
        ],
      ],
      'w' => [
        'label' => 'Wide',
        'ratio' => [
          'x' => 3,
          'y' => 1,
        ],
      ],
      'tw' => [
        'label' => 'Tall wide',
        'ratio' => [
          'x' => 3,
          'y' => 4,
        ],
      ],
      's' => [
        'label' => 'Standard',
        'ratio' => [
          'x' => 4,
          'y' => 3,
        ],
      ],
      't' => [
        'label' => 'Tall',
        'ratio' => [
          'x' => 1,
          'y' => 4,
        ],
      ],
      'q' => [
        'label' => 'Square',
        'ratio' => [
          'x' => 4,
          'y' => 4,
        ],
      ],
    ];

    foreach ($map_image_style_view_mode_default as $id => $config) {
      $this->buildImageStyleViewMode($config['label'], $id, $config['ratio']['x'], $config['ratio']['y']);
    }

  }

  /**
   * Build set of image styles, media view modes, and block_content view modes.
   *
   * @param $label
   *   The label.
   * @param $id
   *   The ID.
   * @param $x
   *   The x ratio.
   * @param $y
   *   The y ratio.
   * @param string[] $multipliers
   *   The multipliers for each derivative.
   *
   * @return bool
   *   Success or failure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildImageStyleViewMode($label, $id, $x, $y, $multipliers = [100, 300, 500, 800, 1300]) {

    $entity_helper = \Drupal::service('entity.helper');
    $entity_storage_image_style = $entity_helper->getStorage('image_style');
    $entity_storage_entity_view_mode = $entity_helper->getStorage('entity_view_mode');
    $entity_storage_entity_view_display = $entity_helper->getStorage('entity_view_display');
    $entity_storage_crop_type = $entity_helper->getStorage('crop_type');

    $entity_view_mode_entity_type_id = [
      'block_content',
      'media',
    ];

    foreach ($multipliers as $multiplier) {

      $entity_id_image_style = "{$id}_{$multiplier}";
      $entity_label_image_style = "{$label} (x{$multiplier})";

      $width = $x * $multiplier;
      $height = $y * $multiplier;
      $ratio = "{$x}:{$y}";

      if (!$entity_crop_type = $entity_storage_crop_type->load($entity_id_image_style)) {
        $entity_crop_type = $entity_storage_crop_type->create([
          'id' => $entity_id_image_style,
        ]);
      }

      $entity_crop_type->set('label', $entity_label_image_style);
      $entity_crop_type->set('aspect_ratio', $ratio);
      $entity_crop_type->set('soft_limit_width', $width);
      $entity_crop_type->set('soft_limit_height', $height);
      $entity_crop_type->set('langcode', 'en');
      $entity_crop_type->set('status', TRUE);
      $entity_crop_type->save();

      // Image style.

      /** @var \Drupal\image\ImageStyleInterface $entity_image_style */

      if (!$entity_image_style = $entity_storage_image_style->load($entity_id_image_style)) {
        $entity_image_style = $entity_storage_image_style->create([
          'name' => $entity_image_style,
        ]);
      }

      $entity_values_image_style = [
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'name' => $entity_id_image_style,
        'label' => $entity_label_image_style,
        'effects' => [],
      ];
      foreach ($entity_values_image_style as $field_name => $field_value) {
        $entity_image_style->set($field_name, $field_value);
      }

      $entity_image_style->addImageEffect([
        'id' => 'crop_crop',
        'weight' => 1,
        'data' => [
          'crop_type' => $entity_crop_type->id(),
        ],
      ]);

      $entity_image_style->save();

      // Entity view modes for media and block_content.
      foreach ($entity_view_mode_entity_type_id as $entity_type_id) {

        $entity_id_view_mode = "{$entity_type_id}.{$entity_id_image_style}";
        if (!$entity_view_mode = $entity_storage_entity_view_mode->load($entity_id_view_mode)) {
          $entity_view_mode = $entity_storage_entity_view_mode->create([
            'langcode' => 'en',
            'status' => TRUE,
            'dependencies' => [],
            'id' => "{$entity_type_id}.{$entity_id_image_style}",
            'targetEntityType' => $entity_type_id,
            'cache' => TRUE,
          ]);
        }

        $entity_view_mode->set('label', $entity_label_image_style);
        $entity_view_mode->save();
      }

      $entity_view_display_media_base_entity_id = "media.image.default";
      $entity_view_display_block_content_base_entity_id = "block_content.image.default";

      $entity_view_display_media_new_entity_id = "media.image.{$entity_id_image_style}";
      $entity_view_display_block_content_new_entity_id = "block_content.image.{$entity_id_image_style}";

      $entity_view_display_media_base_entity = $entity_storage_entity_view_display->load($entity_view_display_media_base_entity_id);
      $entity_view_display_block_content_base_entity = $entity_storage_entity_view_display->load($entity_view_display_block_content_base_entity_id);

      $entity_view_display_media_component_image = $entity_view_display_media_base_entity->getComponent('field_media_image');
      $entity_view_display_block_content_component_field_image = $entity_view_display_block_content_base_entity->getComponent('field_media');

      /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display_media */
      if (!$entity_view_display_media = $entity_storage_entity_view_display->load($entity_view_display_media_new_entity_id)) {
        $entity_view_display_media = $entity_view_display_media_base_entity->createDuplicate();
      }

      $entity_view_display_media->set('mode', $entity_id_image_style);
      $entity_view_display_media->set('content', []);
      $entity_view_display_media_component_image['settings']['image_style'] = $entity_id_image_style;
      $entity_view_display_media->setComponent('field_media_image', $entity_view_display_media_component_image);
      $entity_view_display_media->save();

      if (!$entity_view_display_block_content = $entity_storage_entity_view_display->load($entity_view_display_block_content_new_entity_id)) {
        $entity_view_display_block_content = $entity_view_display_block_content_base_entity->createDuplicate();
      }

      $entity_view_display_block_content->set('mode', $entity_id_image_style);
      $entity_view_display_block_content->set('content', []);
      $entity_view_display_block_content_component_field_image['settings']['view_mode'] = $entity_id_image_style;
      $entity_view_display_block_content->setComponent('field_media', $entity_view_display_block_content_component_field_image);
      $entity_view_display_block_content->save();

    }

    return TRUE;
  }

  /**
   * Convert Drupal blocks to block component entities wrapping blocks.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function convertBlockToComponent() {

    $entity_storage_entity_view_display = $this->entityHelper->getStorage('entity_view_display');

    $entity_view_displays = $entity_storage_entity_view_display->loadMultiple();

    // Get all layout fields.
    $fields = $this->entityHelper->getStorage('field_storage_config')->loadByProperties([
      'type' => 'layout_section',
    ]);

    /** @var \Drupal\field\FieldStorageConfigStorage $field */
    foreach ($fields as $field) {
      $target_entity_type_id = $field->get('entity_type');
      $entity_storage_field_target_entity_type = $this->entityHelper->getStorage($target_entity_type_id);
      $table_name = "{$target_entity_type_id}__layout_builder__layout";
      $entity_ids = $this->database->select($table_name, 'lb')
        ->fields('lb', ['entity_id'])
        ->execute()
        ->fetchAll();
      foreach ($entity_ids as $entity_id_row) {
        $entity = $entity_storage_field_target_entity_type->load($entity_id_row->entity_id);

        $layout_builder_config_values = $entity->layout_builder__layout->getValue();
        $layout_builder_config = [];
        foreach ($layout_builder_config_values as $delta => $field_value) {
          $layout_builder_config[] = $field_value['section'];
        }
        if ($new_layout_builder_config = $this->convertBlockToFieldSingle($layout_builder_config, $entity->getEntityTypeId())) {

          foreach ($new_layout_builder_config as $key => $section) {
            $new_layout_builder_config[$key] = [
              'section' => $section,
            ];
          }

          $entity->set('layout_builder__layout', $new_layout_builder_config);
          $entity->save();
        }

      }
    }

    foreach ($entity_view_displays as $entity_id => $entity_view_display) {

      $third_party_settings = $entity_view_display->get('third_party_settings');

      if (empty($third_party_settings['layout_builder']) || empty($third_party_settings['layout_builder']['enabled']) || empty($third_party_settings['layout_builder']['sections'])) {
        continue;
      }

      $target_entity_type_id = $entity_view_display->getTargetEntityTypeId();

      if ($new_layout_builder_config = $this->convertBlockToFieldSingle($third_party_settings['layout_builder']['sections'], $target_entity_type_id)) {
        $this->logger->notice("Updating entity view ID @entity_id.", [
          '@entity_id' => $entity_view_display->id(),
        ]);
        $third_party_settings['layout_builder']['sections'] = $new_layout_builder_config;
        $entity_view_display->set('third_party_settings', $third_party_settings);
        $entity_view_display->save();
      }

    }

  }

  /**
   * @param $layout_builder_config
   * @param $target_entity_type_id
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function convertBlockToFieldSingle($layout_builder_config, $target_entity_type_id) {
    if ($target_entity_type_id == DesignSystem::ENTITY_TYPE_ID_COMPONENT) {
      $d = 1;
    }

    $changed = FALSE;
    $entity_storage_component = $this->entityHelper->getStorage(DesignSystem::ENTITY_TYPE_ID_COMPONENT);

    /**
     * @var int $section_delta
     * @var \Drupal\layout_builder\Section $section
     */
    foreach ($layout_builder_config as $section_delta => $section) {
      $section_components = $section->getComponents();

      /**
       * @var string $uuid
       * @var \Drupal\layout_builder\SectionComponent $component
       */
      foreach ($section_components as $uuid => $component) {
        if (!$component_config = $component->get('configuration')) {
          continue;
        }
        if ($component_config['provider'] == 'design_system') {
          continue;
        }

        // Create new block component.
        $entity_component = $entity_storage_component->create([
          'type' => 'block',
        ]);

        // If block has label, creating heading component for block component.
        if (!empty($component_config['label_display']) && !empty($component_config['label'])) {
          $entity_component_heading = $entity_storage_component->create([
            'type' => 'heading',
            'field_heading' => [
              'value' => $component_config['label'],
              'format' => 'raw',
            ],
          ]);
          $entity_component_heading->save();
          $entity_component->set('field_cmp_heading', [
            'target_id' => $entity_component_heading->id(),
            'target_revision_id' => $entity_component_heading->getRevisionId(),
          ]);
          $component_config['label_display'] = 0;
          unset($component_config['label']);
        }

        if (!empty($component_config['context_mapping']['view_mode'])) {
          unset($component_config['context_mapping']['view_mode']);
        }

        $block_field_block_config = $component_config;

        if (!empty($block_field_block_config['context_mapping']['entity']) && ($block_field_block_config['context_mapping']['entity'] == 'layout_builder.entity')) {
          unset($block_field_block_config['context_mapping']['entity']);
        }

        // Set block_field config.
        $block_field_value = [
          'plugin_id' => $component_config['id'],
          'settings' => $block_field_block_config,
        ];

        $entity_component->set('field_block', $block_field_value);

        // Map block classes.
        if ($layout_builder_component_block_config = $component->getThirdPartySettings('design_system')) {
          if (!empty($layout_builder_component_block_config['wrapper']['class'])) {
            $entity_component->set('class_container', $layout_builder_component_block_config['wrapper_class']);
          }
          if (!empty($layout_builder_component_block_config['wrapper']['content_class'])) {
            $entity_component->set('class_inner', $layout_builder_component_block_config['content_class']);
          }
          $component->set('third_party_settings', []);
        }

        $entity_component->save();

        // Update layout builder section component configuration.
        $component_config['id'] = "component:block:user";
        $component_config['component'] = $entity_component->id();
        $component_config['provider'] = 'design_system';

        if (!empty($component_config['formatter'])) {
          unset($component_config['formatter']);
        }

        $component->setConfiguration($component_config);
        $changed = TRUE;
      }
    }

    if (!$changed) {
      return FALSE;
    }
    return $layout_builder_config;
  }

}
