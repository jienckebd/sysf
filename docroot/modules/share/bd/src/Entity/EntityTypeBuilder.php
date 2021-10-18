<?php

namespace Drupal\bd\Entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bd\Config\ProcessorInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;

/**
 * Class EntityTypeBuilder.
 */
class EntityTypeBuilder {

  use StringTranslationTrait;

  /**
   * The entity type ID of the entity type.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_ENTITY_TYPE = 'entity_type';

  /**
   * The entity type ID of the entity operation.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_ENTITY_OPERATION = 'entity_operation';

  /**
   * The entity type ID of the entity context.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_ENTITY_CONTEXT = 'entity_context';

  /**
   * The entity type ID of the entity template.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_ENTITY_TEMPLATE = 'entity_template';

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  protected $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity definiton update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManager
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The installed entity definition repository service.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * The entity builder.
   *
   * @var \Drupal\bd\Entity\EntityBuilder
   */
  protected $entityBuilder;

  /**
   * The entity builder.
   *
   * @var \Drupal\bd\Entity\EntityBulkBuilder
   */
  protected $entityBulkBuilder;

  /**
   * The config processor.
   *
   * @var \Drupal\bd\Config\ProcessorInterface
   */
  protected $configProcessor;

  /**
   * The cache.
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
   * EntityTypeBuilder constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManager $entity_definition_update_manager
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   * @param \Drupal\bd\Entity\EntityBuilder $entity_builder
   * @param \Drupal\bd\Entity\EntityBulkBuilder $entity_bulk_builder
   * @param \Drupal\bd\Config\ProcessorInterface $config_processor
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDefinitionUpdateManager $entity_definition_update_manager,
    EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository,
    EntityBuilder $entity_builder,
    EntityBulkBuilder $entity_bulk_builder,
    ProcessorInterface $config_processor,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
    $this->entityBuilder = $entity_builder;
    $this->entityBulkBuilder = $entity_bulk_builder;
    $this->configProcessor = $config_processor;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * @param $entity_type_id_source
   * @param $entity_type_id_target
   */
  public function syncEntityTypeStructure($entity_type_id_source, $entity_type_id_target) {

    $entity_type_source = $this->entityHelper->getDefinition($entity_type_id_source);
    $entity_type_target = $this->entityHelper->getDefinition($entity_type_id_target);

    $entity_type_id_source_bundle = $entity_type_source->getBundleEntityType();
    $entity_type_id_target_bundle = $entity_type_target->getBundleEntityType();

    $entity_storage_source_bundle = $this->entityHelper->getStorage($entity_type_id_source_bundle);
    $entity_storage_target_bundle = $this->entityHelper->getStorage($entity_type_id_target_bundle);
    $entity_storage_field_storage_config = $this->entityHelper->getStorage('field_storage_config');
    $entity_storage_field_config = $this->entityHelper->getStorage('field_config');

    $map_property_sync = [
      'description',
      'langcode',
      'status',
      'help',
    ];

    $entities_source_bundle = $entity_storage_source_bundle->loadMultiple();

    foreach ($entities_source_bundle as $bundle_id => $entity_bundle) {

      if ($entity_storage_target_bundle->load($bundle_id)) {
        continue;
      }

      $entity_bundle_target = $entity_storage_target_bundle->create([
        $entity_type_target->getKey('id') => $bundle_id,
      ]);

      $entity_bundle_target->set($entity_type_target->getKey('label'), $entity_bundle->label());

      foreach ($map_property_sync as $property) {
        if ($property_value = $entity_bundle->get($property)) {
          $entity_bundle_target->set($property, $property_value);
        }
      }

      $entity_bundle_target->save();

    }

    $entities_field_storage_config = $entity_storage_field_storage_config->loadByProperties([
      'entity_type' => $entity_type_id_source,
    ]);

    foreach ($entities_field_storage_config as $entity_field_storage_config_source) {

      $source_id = $entity_field_storage_config_source->id();
      $target_id = str_replace("{$entity_type_id_source}.", "{$entity_type_id_target}.", $source_id);

      if ($entity_storage_field_storage_config->load($target_id)) {
        continue;
      }

      $entity_field_storage_config_target = $entity_field_storage_config_source->createDuplicate();
      $entity_field_storage_config_target->set('id', $target_id);
      $entity_field_storage_config_target->set('entity_type', $entity_type_id_target);
      $entity_field_storage_config_target->save();

    }

    foreach ($entities_source_bundle as $bundle_id => $entity_bundle) {

      $entities_field_config = $entity_storage_field_storage_config->loadByProperties([
        'entity_type' => $entity_type_id_source,
        'bundle' => $bundle_id,
      ]);

      foreach ($entities_field_config as $entity_field_config_source) {

        $source_id = $entity_field_config_source->id();
        $target_id = str_replace("{$entity_type_id_source}.", "{$entity_type_id_target}.", $source_id);

        if ($entity_storage_field_config->load($target_id)) {
          continue;
        }

        $entity_field_config_target = $entity_field_config_source->createDuplicate();
        $entity_field_config_target->set('id', $target_id);
        $entity_field_config_target->set('entity_type', $entity_type_id_target);
        $entity_field_config_target->set('bundle', $bundle_id);
        $entity_field_config_target->save();

      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public function initEntityType($entity_type_id, $reset = FALSE) {

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);

    // Step 1: First build entity type config.
    if ($bundle_config_list = $this->entityHelper->getBundleDefinitionConfig($entity_type_id)) {
      foreach ($bundle_config_list as $bundle_id => $bundle_config) {

        // Build bundle entity.
        if (!empty($bundle_config['definition'])) {
          $this->buildBundle($entity_type_id, $bundle_id, $bundle_config['definition']);
        }

        // Build entity fields.
        if (!empty($bundle_config['field']['definition'])) {
          $this->entityFieldManager->buildFieldMultiple($entity_type_id, $bundle_id, $bundle_config['field']['definition'], $reset);
        }

        // Build entity_form_display.
        if (!empty($bundle_config['entity_form_display']['definition'])) {
          $this->buildEntityDisplayMultiple($entity_type_id, $bundle_id, 'form', $bundle_config['entity_form_display']['definition']);
        }

        // Build entity_view_display.
        if (!empty($bundle_config['entity_view_display']['definition'])) {
          $this->buildEntityDisplayMultiple($entity_type_id, $bundle_id, 'view', $bundle_config['entity_view_display']['definition']);
        }

      }
    }

    $this->buildEntityTypeResource($entity_type_id);

    // $this->entityBulkBuilder->buildByConfig($entity_type_id);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBundleResource($entity_type_id, $bundle_id, $resource_type_id = NULL, $reset = FALSE) {

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);

    if ($bundle_config = $this->entityHelper->getBundleDefinitionConfig($entity_type_id, $bundle_id)) {

      // Build bundle entity.
      if (empty($resource_type_id) || ($resource_type_id == 'bundle')) {
        if (!empty($bundle_config['definition'])) {
          $this->buildBundle($entity_type_id, $bundle_id, $bundle_config['definition']);
        }
      }

      // Build entity fields.
      if (empty($resource_type_id) || ($resource_type_id == 'field')) {
        if (!empty($bundle_config['field']['definition'])) {
          $this->entityFieldManager->buildFieldMultiple($entity_type_id, $bundle_id, $bundle_config['field']['definition'], $reset);
        }
      }

      // Build entity_form_display.
      if (empty($resource_type_id) || ($resource_type_id == 'entity_form_display')) {
        if (!empty($bundle_config['entity_form_display']['definition'])) {
          $this->buildEntityDisplayMultiple($entity_type_id, $bundle_id, 'form', $bundle_config['entity_form_display']['definition']);
        }
      }

      // Build entity_view_display.
      if (empty($resource_type_id) || ($resource_type_id == 'entity_view_display')) {
        if (!empty($bundle_config['entity_view_display']['definition'])) {
          $this->buildEntityDisplayMultiple($entity_type_id, $bundle_id, 'view', $bundle_config['entity_view_display']['definition']);
        }
      }

    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntityTypeResourceAll($resource_entity_type_id_match = NULL) {
    foreach ($this->entityHelper->getDefinitions() as $entity_type_id => $entity_type) {
      $this->buildEntityTypeResource($entity_type_id, $resource_entity_type_id_match);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntityTypeResource($entity_type_id, $resource_entity_type_id_match = []) {

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);

    if (!$resource_config_list = $entity_type->get('resource')) {
      return FALSE;
    }

    if (!empty($resource_config_list['per_entity_type']['required']['template'])) {
      foreach ($resource_config_list['per_entity_type']['required']['template'] as $resource_entity_type_id => $resource_config_list_entity_type) {

        if (!empty($resource_entity_type_id_match)) {
          if (is_array($resource_entity_type_id_match)) {
            if (!in_array($resource_entity_type_id, $resource_entity_type_id_match)) {
              continue;
            }
          }
          else {
            if ($resource_entity_type_id_match != $resource_entity_type_id) {
              continue;
            }
          }
        }

        foreach ($resource_config_list_entity_type as $resource_id => $resource_config) {
          $template_id = "{$resource_entity_type_id}.{$resource_id}";
          $variables = [];
          $variables['entity_type_id'] = $entity_type_id;
          if (!$this->entityBuilder->fromTemplate($template_id, $variables, TRUE)) {
            continue;
          }
          $this->logger->notice("Building resource @template_id for entity type @entity_type_id.", [
            '@template_id' => $template_id,
            '@entity_type_id' => $entity_type_id,
          ]);
          $this->entityBuilder->save();
        }
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBundle($entity_type_id, $bundle_id, array $bundle_config) {

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);

    if (!$entity_type_id_bundle = $entity_type->getBundleEntityType()) {
      $this->logger->warning("Unable to build bundle because entity type is not bundled.");
      return FALSE;
    }

    $entity_type_bundle = $this->entityHelper->getDefinition($entity_type_id_bundle);
    $entity_storage_bundle = $this->entityHelper->getStorage($entity_type_id_bundle);
    $entity_key_bundle = $entity_type_bundle->getKeys();

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_bundle */
    if (!$entity_bundle = $entity_storage_bundle->load($bundle_id)) {
      $entity_bundle = $entity_storage_bundle->create([
        $entity_key_bundle['id'] => $bundle_id,
      ]);
    }

    foreach ($bundle_config as $key => $value) {
      $entity_bundle->set($key, $value);
    }

    $entity_bundle->save();

  }

  /**
   * {@inheritdoc}
   */
  public function buildEntityDisplayMultiple($entity_type_id, $bundle_id, $display_context_id, array $entity_display_config_multiple) {
    foreach ($entity_display_config_multiple as $display_mode_id => $entity_display_config) {
      $this->buildEntityDisplay($entity_type_id, $bundle_id, $display_context_id, $display_mode_id, $entity_display_config);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntityDisplay($entity_type_id, $bundle_id, $display_context_id, $display_mode_id, array $entity_display_config) {
    $entity_id_entity_form_display = "{$entity_type_id}.{$bundle_id}.{$display_mode_id}";

    $display_context_id_full = "entity_{$display_context_id}_display";

    $entity_storage_entity_form_display = $this->entityHelper->getStorage($display_context_id_full);

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);

    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $entity_display */
    if (!$entity_display = $entity_storage_entity_form_display->load($entity_id_entity_form_display)) {
      $entity_display = $entity_storage_entity_form_display->create([
        'id' => $entity_id_entity_form_display,
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle_id,
        'mode' => $display_mode_id,
      ]);
    }

    $entity_display->set('status', TRUE);
    $entity_display->set('langcode', 'en');

    if ($bundle_id == 'style') {
      $group_deriver_entity = $this->entityHelper->getStorage('dom')
        ->loadByProperties([
          'bundle' => 'property_group',
        ]);

      if (!empty($group_deriver_entity)) {
        foreach ($group_deriver_entity as $entity_id => $entity) {
          $entity_display_config['group'][$entity_id] = [
            'type' => 'tab',
            'label' => $entity->label(),
          ];
        }
      }
    }

    $template = !empty($entity_display_config['template']) ? $entity_display_config['template'] : 'default';
    $group = !empty($entity_display_config['group']) ? $entity_display_config['group'] : [];

    $entity_display->setThirdPartySetting('field_layout', 'id', 'standard_1');
    $entity_display->setThirdPartySetting('field_layout', 'settings', []);

    $third_party_settings_field_group = [];

    $weight = 0;
    if ($template == 'vertical_tabs') {
      $third_party_settings_field_group['group_tabs'] = [
        'children' => [
          'group_general',
        ],
        'parent_name' => '_none',
        'label' => 'Tabs',
        'weight' => 0,
        'format_type' => 'tabs',
        'format_settings' => [
          'id' => '',
          'classes' => '',
          'direction' => 'vertical',
          'label' => 'Tabs',
          'region' => 'row1_col1',
        ],
      ];

      $third_party_settings_field_group['group_general'] = [
        'children' => [
          'label',
          'label_display',
        ],
        'parent_name' => 'group_tabs',
        'label' => 'General Settings',
        'weight' => $weight,
        'format_type' => 'tab',
        'format_settings' => [
          'id' => '',
          'classes' => '',
          'direction' => 'vertical',
          'required_fields' => TRUE,
          'region' => 'row1_col1',
          'formatter' => 'open',
        ],
      ];
    }
    else {
      // @todo handle other form templates.
      return;
    }

    foreach ($group as $group_id => $group_config) {

      $weight += 10;

      $group_key = "group_{$group_id}";
      $third_party_settings_field_group[$group_key] = [
        'children' => [],
        'parent_name' => 'group_tabs',
        'label' => $group_config['label'],
        'weight' => $weight,
        'format_type' => 'tab',
        'format_settings' => [
          'id' => '',
          'classes' => '',
          'direction' => 'vertical',
          'required_fields' => FALSE,
          'region' => 'row1_col1',
        ],
      ];

      $third_party_settings_field_group['group_tabs']['children'][] = $group_key;

    }

    foreach ($field_definitions as $field_name => $field_definition) {

      if (!method_exists($field_definition, 'getThirdPartySetting')) {
        continue;
      }
      if (!$group_id = $field_definition->getThirdPartySetting('bd', 'group')) {
        $group_id = 'general';
      }

      $data_type_id = $field_definition->getItemDefinition()->getDataType();
      [$field_item, $field_item_type] = explode(':', $data_type_id);

      $component = [];
      $component['weight'] = 0;
      $entity_display->setComponent($field_name, $component);

      $field_type_plugin_manager = \Drupal::service('plugin.manager.field.field_type');
      $field_type_plugin_definition = $field_type_plugin_manager->getDefinition($field_item_type);
      $field_widget_plugin_manager = \Drupal::service('plugin.manager.field.widget');
      $field_formatter_plugin_manager = \Drupal::service('plugin.manager.field.formatter');

      $group_key = "group_{$group_id}";
      $third_party_settings_field_group[$group_key]['children'][] = $field_name;

      if (empty($third_party_settings_field_group[$group_key]['required_fields']) && $field_definition->isRequired()) {
        $third_party_settings_field_group[$group_key]['required_fields'] = TRUE;
      }

    }

    foreach ($third_party_settings_field_group as $key => $value) {
      $entity_display->setThirdPartySetting('field_group', $key, $value);
    }

    $entity_display->save();
  }

  /**
   * {@inheritdoc}
   */
  public function collapseEntityTypeMultiple($entity_type_id) {
  }

  /**
   *
   */
  public function repairEntityType($entity_type_id) {

    $key_value = \Drupal::keyValue('entity.definitions.installed');

    $entity_type_key = "{$entity_type_id}.entity_type";
    $data = $key_value->get($entity_type_key);
    $key_value->set($entity_type_key, \Drupal::service('entity.helper')->getDefinition($entity_type_id));

    $field_storage_key = "{$entity_type_id}.field_storage_definitions";
    $data = $key_value->get($field_storage_key);

  }

  /**
   * @param $source_entity_type_id
   * @param $target_entity_type_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cloneEntityTypeBundles($source_entity_type_id, $target_entity_type_id) {

    $entity_storage_field_storage_config = $this->entityHelper->getStorage('field_storage_config');
    $entity_storage_field_config = $this->entityHelper->getStorage('field_config');

    $entity_type_source = $this->entityHelper->getDefinition($source_entity_type_id);
    $bundle_entity_type_id_source = $entity_type_source->getBundleEntityType();

    $entity_type_target = $this->entityHelper->getDefinition($target_entity_type_id);
    $bundle_entity_type_id_target = $entity_type_target->getBundleEntityType();

    $source_field_storage_configs = $source_field_storage_config = $entity_storage_field_storage_config->loadByProperties([
      'entity_type' => $source_entity_type_id,
    ]);

    foreach ($this->entityHelper->getStorage($bundle_entity_type_id_source)->loadMultiple() as $source_bundle) {

      // @todo create target bundle entity. This only copies its fields.
      $bundle_id = $source_bundle->id();

      $source_field_configs = $entity_storage_field_config->loadByProperties([
        'entity_type' => $source_entity_type_id,
        'bundle' => $bundle_id,
      ]);

      $target_field_storage_configs = $source_field_storage_config = $entity_storage_field_storage_config->loadByProperties([
        'entity_type' => $target_entity_type_id,
      ]);

      $target_field_configs = $entity_storage_field_config->loadByProperties([
        'entity_type' => $target_entity_type_id,
        'bundle' => $bundle_id,
      ]);

      /** @var \Drupal\field\FieldConfigInterface $field_config */
      foreach ($source_field_configs as $field_config) {

        $field_name = $field_config->getName();

        // Check if field storage config already exists.
        $field_storage_config_id_target = "{$target_entity_type_id}.{$field_name}";
        if (empty($target_field_storage_configs[$field_storage_config_id_target])) {

          if (empty($source_field_storage_configs["{$source_entity_type_id}.{$field_name}"])) {
            $this->logger->warning("Missing source field storage config.");
            continue;
          }

          $source_field_storage_config = $source_field_storage_configs["{$source_entity_type_id}.{$field_name}"];

          $target_field_storage_config = $source_field_storage_config->createDuplicate();
          $target_field_storage_config->set('id', $field_storage_config_id_target);
          $target_field_storage_config->set('entity_type', $target_entity_type_id);
          $target_field_storage_config->save();

        }

        $field_config_id_target = "{$target_entity_type_id}.{$bundle_id}.{$field_name}";
        if (empty($target_field_configs[$field_config_id_target])) {

          $target_field_config = $field_config->createDuplicate();
          $target_field_config->set('id', $field_config_id_target);
          $target_field_config->set('entity_type', $target_entity_type_id);
          $target_field_config->set('bundle', $bundle_id);
          $target_field_config->save();

        }

      }

    }

  }

  /**
   *
   */
  public function buildTemplateFromEntity($entity_type_id, $entity_id, $entity_type_id_context, $template_key, $template_bundle = 'default') {

    $entity_storage_source = $this->entityHelper->getStorage($entity_type_id);
    $entity_storage_template = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_ENTITY_TEMPLATE);

    $entity_source = $entity_storage_source->load($entity_id);

    $template_machine_name = "{$entity_type_id}.{$template_key}";

    $entity_values_entity_template = [
      'machine_name' => $template_machine_name,
      'bundle' => $template_bundle,
    ];

    if ($entity_template = $entity_storage_template->loadByProperties($entity_values_entity_template)) {
      $entity_template = reset($entity_template);
    }
    else {
      $entity_template = $entity_storage_template->create($entity_values_entity_template);
    }

    $entity_template->set('source', [
      'target_type' => $entity_source->getEntityTypeId(),
      'target_id' => $entity_source->id(),
    ]);

    $data = [];

    $data['variables']['entity_type_id'] = $entity_type_id_context;
    $entity_template->set('data', $data);

    $entity_template->save();

  }

  /**
   *
   */
  public function deriveFieldFromField($entity_type_id, $bundle_id, $field_type_source, $field_type_target, $target_settings = [], $field_name_suffix = 'v2') {

    $entity_storage_field_storage_config = $this->entityHelper->getStorage('field_storage_config');
    $entity_storage_field_config = $this->entityHelper->getStorage('field_config');

    $source_field_storage_config = $entity_storage_field_storage_config->loadByProperties([
      'entity_type' => $entity_type_id,
      'type' => 'color_field_type',
    ]);

    $source_field_config = $entity_storage_field_config->loadByProperties([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle_id,
      'field_type' => 'color_field_type',
    ]);

    foreach ($source_field_storage_config as $field_name => $field_storage_definition_source) {

      /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_definition_source */

      $field_name = $field_storage_definition_source->getName();
      $id = $field_storage_definition_source->id();
      $field_name_target = "{$field_name}_{$field_name_suffix}";
      $id_target = "{$id}_{$field_name_suffix}";

      /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config_target */
      if (!$field_storage_config_target = $entity_storage_field_storage_config->load($id_target)) {
        $field_storage_config_target = $field_storage_definition_source->createDuplicate();
      }

      $field_storage_config_target->set('field_name', $field_name_target);
      $field_storage_config_target->set('type', $field_type_target);
      $field_storage_config_target->set('dependencies', []);
      $field_storage_config_target->setSetting('target_type', 'dom');

      $field_storage_config_target->save();

    }

    foreach ($source_field_config as $field_name => $field_config_source) {

      /** @var \Drupal\field\FieldStorageConfigInterface $field_config_source */

      $field_name = $field_config_source->getName();
      $id = $field_config_source->id();
      $field_name_target = "{$field_name}_{$field_name_suffix}";
      $id_target = "{$id}_{$field_name_suffix}";

      /** @var \Drupal\field\FieldStorageConfigInterface $field_config_target */
      if (!$field_config_target = $entity_storage_field_config->load($id_target)) {
        $field_config_target = $field_config_source->createDuplicate();
      }

      $field_config_target->set('field_name', $field_name_target);
      $field_config_target->set('field_type', $field_type_target);
      $field_config_target->setSettings($target_settings);
      $field_config_target->set('dependencies', []);
      $field_config_target->set('fieldStorage', NULL);
      $field_config_target->save();

    }

  }

  /**
   * Uninstall missing entity types.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function uninstallRemovedEntityTypes() {
    foreach ($this->entityLastInstalledSchemaRepository->getLastInstalledDefinitions() as $installed_entity_type_id => $installed_entity_type) {

      if (!$this->entityHelper->getDefinition($installed_entity_type_id, FALSE)) {

        $this->logger->notice("Uninstalling missing entity type @installed_entity_type_id", [
          '@installed_entity_type_id' => $installed_entity_type_id,
        ]);

        $this->entityDefinitionUpdateManager->uninstallEntityType($installed_entity_type);

        $this->logger->notice("Uninstalled missing entity type @installed_entity_type_id", [
          '@installed_entity_type_id' => $installed_entity_type_id,
        ]);

      }

    }
  }

}
