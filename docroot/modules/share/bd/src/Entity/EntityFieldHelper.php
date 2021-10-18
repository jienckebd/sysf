<?php

namespace Drupal\bd\Entity;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\bd\Field\ComputedDynamicEntityReferenceFieldItemList;
use Drupal\bd\Field\ComputedEntityReferenceRevisionsFieldItemList;
use Drupal\bd\Field\ComputedFieldValueGenericFieldItemList;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Entity field helper.
 */
class EntityFieldHelper {
  use StringTranslationTrait;

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
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The entity logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * EntityFieldHelper constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger,
    TranslationInterface $string_translation
  ) {
    $this->setStringTranslation($string_translation);
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * @param array $fields
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @throws \Exception
   */
  public function buildBaseFieldDefinitions(array &$fields, EntityTypeInterface $entity_type) {

    $t_context = $this->entityHelper->getTContext($entity_type);

    $entity_type_id = $entity_type->id();
    $entity_keys = $entity_type->getKeys();

    if ($entity_type_base_field_config = $entity_type->get('field')) {

      if (!empty($entity_type_base_field_config['definition'])) {
        foreach ($entity_type_base_field_config['definition'] as $field_name => $custom_field_config) {

          if (!empty($custom_field_config['template'])) {
            $custom_field_config = $this->buildFieldDefinitionFromTemplate($custom_field_config);
          }

          $this->processFieldAdd($entity_type_id, $fields, $field_name, $custom_field_config, $t_context);

        }
      }

      if (!empty($entity_type_base_field_config['alter'])) {
        foreach ($entity_type_base_field_config['alter'] as $field_name => $field_definition_config) {
          if (empty($fields[$field_name])) {
            \Drupal::logger('entity')->warning("Missing field: @field_name", [
              '@field_name' => $field_name,
            ]);
            continue;
          }

          /** @var \Drupal\Core\Field\BaseFieldDefinition $field */
          $field = $fields[$field_name];

          $this->processFieldConfig($field, $field_definition_config, $t_context);
        }
      }
    }

    $config_display_configurable_disable = \Drupal::configFactory()->getConfig('bd.entity.field.common')->get('display_configurable_disable');
    $entity_keys_flipped = array_flip($entity_keys);
    $revision_metadata_keys = $entity_type->get('revision_metadata_keys');
    $revision_metadata_keys_flipped = array_flip($revision_metadata_keys);

    // Set all fields as display configurable.
    foreach ($fields as $field_name => $field) {

      $display_configurable_view = TRUE;
      $display_configurable_form = TRUE;

      $entity_key = isset($entity_keys_flipped[$field_name]) ? $entity_keys_flipped[$field_name] : NULL;
      $revision_metadata_key = isset($revision_metadata_keys_flipped[$field_name]) ? $revision_metadata_keys_flipped[$field_name] : NULL;

      if ($entity_key && in_array($entity_key, $config_display_configurable_disable['view']['entity_key'])) {
        $display_configurable_view = FALSE;
      }
      if ($entity_key && in_array($entity_key, $config_display_configurable_disable['form']['entity_key'])) {
        $display_configurable_form = FALSE;
      }

      if ($revision_metadata_key && in_array($revision_metadata_key, $config_display_configurable_disable['view']['revision_metadata_key'])) {
        $display_configurable_view = FALSE;
      }
      if ($revision_metadata_key && in_array($revision_metadata_key, $config_display_configurable_disable['form']['revision_metadata_key'])) {
        $display_configurable_form = FALSE;
      }

      if (!empty($config_display_configurable_disable['view']['field_name']) && in_array($field_name, $config_display_configurable_disable['view']['field_name'])) {
        $display_configurable_view = FALSE;
      }
      if (!empty($config_display_configurable_disable['form']['field_name']) && in_array($field_name, $config_display_configurable_disable['form']['field_name'])) {
        $display_configurable_form = FALSE;
      }

      if ($display_configurable_view) {
        $field->setDisplayConfigurable('view', TRUE);
      }

      if ($display_configurable_form) {
        $field->setDisplayConfigurable('form', TRUE);
      }

    }

  }

  /**
   * @param $fields
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param $bundle
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildBundleFieldDefinitions(&$fields, EntityTypeInterface $entity_type, $bundle) {

    $entity_type_id = $entity_type->id();

    $bundle_field_definitions = $this->entityHelper
      ->getStorage('bundle_field_definition')
      ->loadByProperties([
        'entity_type' => $entity_type_id,
        'bundle' => $bundle,
      ]);

    if (!empty($bundle_field_definitions)) {
      foreach ($bundle_field_definitions as $entity_id => $bundle_field_definition) {

        $plugin_field_type = $bundle_field_definition->get('plugin_field_type');
        $plugin_computed_field_value = $bundle_field_definition->get('plugin_computed_field_value');
        $bundle_field_definition_id = $bundle_field_definition->id();
        $bundle_field_definition_id_pieces = explode('.', $bundle_field_definition_id);

        $field_name = $bundle_field_definition_id_pieces[2];
        $field_type = $plugin_field_type['plugin_id'];
        $computed_field_value_plugin_id = $plugin_computed_field_value['plugin_id'];
        $computed_field_value_plugin_config = isset($plugin_computed_field_value['plugin_config']) ? $plugin_computed_field_value['plugin_config'] : [];

        // @todo get target type from new field on bundle field definition.
        $target_entity_type = $entity_type_id;

        $field = BaseFieldDefinition::create($field_type);
        $field->setLabel($bundle_field_definition->label());
        $field->setDescription($bundle_field_definition->get('description'));
        $field->setSetting('target_type', $target_entity_type);
        $field->setSetting('plugin_id', $computed_field_value_plugin_id);
        $field->setSetting('plugin_config', $computed_field_value_plugin_config);
        $field->setComputed(TRUE);
        $field->setDisplayConfigurable('view', TRUE);
        $field->setName($field_name);
        $field->setTargetEntityTypeId($entity_type_id);

        $cardinality = $bundle_field_definition->get('cardinality') ?: 1;
        $field->setCardinality($cardinality);

        $map_field_type_computed_class = [
          'entity_reference_revisions' => ComputedEntityReferenceRevisionsFieldItemList::class,
          'dynamic_entity_reference' => ComputedDynamicEntityReferenceFieldItemList::class,
          'entity_reference' => ComputedEntityReferenceRevisionsFieldItemList::class,
        ];

        if (isset($map_field_type_computed_class[$field_type])) {
          $class = $map_field_type_computed_class[$field_type];
        }
        else {
          $class = ComputedFieldValueGenericFieldItemList::class;
        }

        $field->setClass($class);

        $fields[$field_name] = $field;
      }
    }

  }

  /**
   * {@inheritDoc}
   */
  public function buildFieldDefinitionFromTemplate($field_definition) {

    $template_id = $field_definition['template'];
    if (!$field_definition_template = $this->getFieldDefinitionTemplate($template_id)) {
      \Drupal::logger('entity')->warning("Missing field definition template @template_id.", [
        '@template_id' => $template_id,
      ]);
      return FALSE;
    }

    // Remove template from built field definition.
    unset($field_definition['template']);
    $built_field_definition = NestedArray::mergeDeep($field_definition, $field_definition_template);
    return $built_field_definition;

  }

  /**
   * {@inheritDoc}
   */
  public function getFieldDefinitionTemplate($template_id = NULL) {
    $field_definition_templates = \Drupal::configFactory()->getConfig('bd.entity.field.common')->get('template');
    if (!empty($template_id)) {
      return $field_definition_templates[$template_id];
    }
    return $field_definition_templates;
  }

  /**
   * @param $entity_type_id
   * @param array $fields
   * @param $field_name
   * @param array $field_definition_config
   * @param array $t_context
   *
   * @throws \Exception
   */
  protected function processFieldAdd($entity_type_id, array &$fields, $field_name, array $field_definition_config = [], array $t_context = []) {

    if (empty($field_definition_config)) {
      $field_definition_config = $this->getFieldDefinitionTemplate($field_name);
    }

    if (isset($field_definition_config['from'])) {
      if (empty($fields[$field_definition_config['from']])) {
        throw new \Exception("From field {$fields[$field_definition_config['from']]} does not exist for {$entity_type_id} / {$field_name}.");
      }
      $field = clone $fields[$field_definition_config['from']];
    }
    else {
      $field = BaseFieldDefinition::create($field_definition_config['type']);
    }

    // Init the field.
    $field->setName($field_name);
    $field->setTargetEntityTypeId($entity_type_id);

    $this->processFieldConfig($field, $field_definition_config, $t_context);

    $fields[$field_name] = $field;
  }

  /**
   *
   */
  protected function processFieldConfig(BaseFieldDefinition $field, $field_definition_config, $t_context) {
    // Derive the field from the field definition config in *.entity.yml.
    if (isset($field_definition_config['label'])) {
      $field->setLabel($this->t($field_definition_config['label'], $t_context));
    }

    if (isset($field_definition_config['description'])) {
      $field->setDescription($this->t($field_definition_config['description'], $t_context));
    }

    if (isset($field_definition_config['settings'])) {
      $field->setSettings($field_definition_config['settings']);
    }

    if (!empty($field_definition_config['read_only'])) {
      $field->setReadOnly($field_definition_config['read_only']);
    }

    if (!empty($field_definition_config['computed'])) {
      $field->setComputed($field_definition_config['computed']);
    }

    if (isset($field_definition_config['class'])) {
      $field->setClass($field_definition_config['class']);
    }

    if (!empty($field_definition_config['required'])) {
      $field->setRequired(TRUE);
      $field->setStorageRequired(TRUE);
    }
    else {
      $field->setRequired(FALSE);
      $field->setStorageRequired(FALSE);
    }

    if (isset($field_definition_config['initial_value'])) {
      $field->setInitialValue($field_definition_config['initial_value']);
    }

    if (isset($field_definition_config['initial_value_from_field'])) {
      $field->setInitialValueFromField($field_definition_config['initial_value_from_field']);
    }

    if (isset($field_definition_config['revisionable'])) {
      $field->setRevisionable(TRUE);
    }

    if (isset($field_definition_config['translatable'])) {
      $field->setTranslatable(TRUE);
    }

    if (isset($field_definition_config['cardinality'])) {
      $field->setCardinality($field_definition_config['cardinality']);
    }

    if (isset($field_definition_config['constraint'])) {
      foreach ($field_definition_config['constraint'] as $constraint_id => $constraint_config) {
        $field->addConstraint($constraint_id, $constraint_config);
      }
    }

    if (isset($field_definition_config['constraints'])) {
      $field->setConstraints($field_definition_config['constraints']);
    }

    if (isset($field_definition_config['display_options'])) {
      foreach ($field_definition_config['display_options'] as $display_context => $display_options) {
        $field->setDisplayOptions($display_context, $display_options);
      }
    }

    if (isset($field_definition_config['display_configurable'])) {
      foreach ($field_definition_config['display_configurable'] as $display_context_id => $configurable) {
        $field->setDisplayConfigurable($display_context_id, $configurable);
      }
    }

    $map_property_method = [
      'default_value' => 'setDefaultValue',
      'default_value_callback' => 'setDefaultValueCallback',
    ];
    foreach ($map_property_method as $property => $method) {
      if (!isset($field_definition_config[$property])) {
        continue;
      }
      if (!method_exists($field, $method)) {
        continue;
      }

      $val = $field_definition_config[$property];

      $field->{$method}($val);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldMultiple($entity_type_id, $bundle_id, array $field_definition_config, $reset = FALSE) {

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    $entity_storage_field_storage_config = $this->entityHelper->getStorage('field_storage_config');
    $entity_storage_field_config = $this->entityHelper->getStorage('field_config');
    $bundle_key = $entity_type->getKey('bundle');

    if ($reset) {
      $this->purgeFieldConfigByBundle($entity_type_id, $bundle_id, TRUE);
    }

    $existing_field_definitions = $this->getFieldDefinitions($entity_type_id, $bundle_id);

    // Build fields.
    $index_field_name_list = [];

    foreach ($field_definition_config as $field_name => $field_definition_config_field) {

      // Skip derivers. This should have been removed above, but be safe.
      if (isset($field_definition_config_field['deriver'])) {
        continue;
      }

      if (empty($field_definition_config_field['type'])) {
        \Drupal::logger('entity')->warning("Missing field type.");
        continue;
      }

      $entity_id_field_storage_config = "{$entity_type_id}.{$field_name}";

      if (isset($field_definition_config_field['field_config']['third_party_settings']['bd']['index'])) {
        $index_field_name_list[] = $field_name;
      }

      /** @var \Drupal\field\FieldStorageConfigInterface $entity_field_storage_config */
      if (!$entity_field_storage_config = $entity_storage_field_storage_config->load($entity_id_field_storage_config)) {
        $entity_field_storage_config = $entity_storage_field_storage_config->create([
          'id' => $entity_id_field_storage_config,
          'field_name' => $field_name,
          'type' => $field_definition_config_field['type'],
          'entity_type' => $entity_type_id,
        ]);
      }

      if (isset($field_definition_config_field['field_storage_config'])) {
        foreach ($field_definition_config_field['field_storage_config'] as $key => $value) {
          $entity_field_storage_config->set($key, $value);
        }
      }

      $entity_field_storage_config->save();

      $entity_id_field_config = "{$entity_type_id}.{$bundle_id}.{$field_name}";

      /** @var \Drupal\field\FieldConfigInterface $entity_field_config */
      if (!$entity_field_config = $entity_storage_field_config->load($entity_id_field_config)) {
        $entity_field_config = $entity_storage_field_config->create([
          'id' => $entity_id_field_config,
          'field_name' => $field_name,
          'type' => $field_definition_config_field['type'],
          'entity_type' => $entity_type_id,
          'bundle' => $bundle_id,
        ]);
      }

      if (isset($field_definition_config_field['field_config'])) {
        foreach ($field_definition_config_field['field_config'] as $key => $value) {
          $entity_field_config->set($key, $value);
        }
      }

      $entity_field_config->save();

    }

    if (!empty($existing_field_definitions['index'])) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition_index */
      $field_definition_index = $existing_field_definitions['index'];
      $field_definition_index->setSetting('field', $index_field_name_list);
      $field_definition_index->save();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function purgeFieldConfigByEntityType($entity_type_id) {

    if (!$entity_bundle_list = $this->entityHelper->getEntityTypeBundleList($entity_type_id)) {
      return FALSE;
    }

    foreach ($entity_bundle_list as $bundle_id => $bundle) {
      $this->purgeFieldConfigByBundle($entity_type_id, $bundle_id);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function purgeFieldConfigByBundle($entity_type_id, $bundle_id, $field_name = NULL, $purge_orphan_field_storage_config = TRUE) {

    $entity_storage_field_storage_config = $this->entityHelper->getStorage('field_storage_config');
    $entity_storage_field_config = $this->entityHelper->getStorage('field_config');

    $existing_field_config = $entity_storage_field_config->loadByProperties([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle_id,
    ]);

    if (!empty($existing_field_config)) {
      foreach ($existing_field_config as $field_name_field_config => $field_config) {

        if (!empty($field_name)) {
          if ($field_name_field_config != $field_name) {
            continue;
          }
        }

        $field_config->delete();

        if (!$purge_orphan_field_storage_config) {
          continue;
        }

        // If this is the last field_config of this field_storage_config,
        // delete the field_storage_config.
        $other_field_config = $entity_storage_field_config->loadByProperties([
          'entity_type' => $entity_type_id,
          'field_name' => $field_name_field_config,
        ]);

        if (empty($other_field_config)) {

          $field_storage_config_multiple = $entity_storage_field_storage_config->loadByProperties([
            'entity_type' => $entity_type_id,
            'field_name' => $field_name_field_config,
          ]);

          if (!empty($field_storage_config_multiple)) {
            foreach ($field_storage_config_multiple as $field_name_field_storage_config => $field_storage_config) {
              $field_storage_config->delete();
            }
          }

        }

      }
    }
  }

  /**
   *
   */
  public function purgeFieldSingle($entity_type_id, array $conditions = []) {
  }

  /**
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function purgeFieldOrphan() {

    $entity_storage_field_config = $this->entityHelper->getStorage('field_config');
    if (!$entity_field_config_list = $entity_storage_field_config->loadMultiple()) {
      return TRUE;
    }

    $processed_entity_type_id_list = [];

    /**
     * @var string $entity_id_field_config
     * @var \Drupal\field\FieldConfigInterface $entity_field_config
     */
    foreach ($entity_field_config_list as $entity_id_field_config => $entity_field_config) {

      $target_entity_type_id = $entity_field_config->getTargetEntityTypeId();
      $target_bundle_id = $entity_field_config->getTargetBundle();
      $field_name = $entity_field_config->getName();

      if (in_array($target_entity_type_id, $processed_entity_type_id_list)) {
        continue;
      }

      try {
        $this->entityHelper->getDefinition($target_entity_type_id);
      }
      catch (PluginNotFoundException $e) {
        // Entity type is missing. Delete fields and then its field storage config.
        $processed_entity_type[] = $target_entity_type_id;
        $this->purgeEntityTypeResource($target_entity_type_id);
      }
      catch (\Exception $e) {
        // Do nothing for other exceptions.
      }

    }

  }

  /**
   * @param $target_entity_type_id
   */
  public function purgeEntityTypeResource($target_entity_type_id) {

    $config_factory = \Drupal::configFactory();

    $configs_to_delete = [];

    $configs_to_delete['field_config'] = $config_factory->listAll("field.field.{$target_entity_type_id}");
    $configs_to_delete['field_storage_config'] = $config_factory->listAll("field.storage.{$target_entity_type_id}");
    $configs_to_delete['entity_form_display'] = $config_factory->listAll("core.entity_form_display.{$target_entity_type_id}");
    $configs_to_delete['entity_view_display'] = $config_factory->listAll("core.entity_view_display.{$target_entity_type_id}");

    foreach ($configs_to_delete as $entity_type_id => $config_id_list) {
      if (empty($config_id_list)) {
        continue;
      }
      foreach ($config_id_list as $config_id) {
        $config = $config_factory->getEditable($config_id);
        $config->delete();
      }
    }

  }

  /**
   *
   */
  public function buildField($entity_type_id, $bundle_id, array $field_definition_config_field) {
  }

  /**
   * @param array $info
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildExtraFields(array &$info) {

    foreach ($this->entityTypeBundleInfo->getAllBundleInfo() as $entity_type_id => $bundles_of_entity_type) {

      foreach ($bundles_of_entity_type as $bundle => $bundle_info) {
        $info[$entity_type_id][$bundle] = $info[$entity_type_id][$bundle] ?? [];
        if ($extra_field_all = $this->buildExtraFieldAll($entity_type_id, $bundle)) {
          $info[$entity_type_id][$bundle] = array_merge_recursive($info[$entity_type_id][$bundle], $extra_field_all);
        }
      }
    }

  }

  /**
   * @param $entity_type_id
   * @param $bundle
   *
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildExtraFieldAll($entity_type_id, $bundle) {

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);

    if (!$entity_type_field_config = $entity_type->get('field')) {
      return FALSE;
    }

    if (empty($entity_type_field_config['extra'])) {
      return FALSE;
    }

    $extra_field = [];

    foreach ($entity_type_field_config['extra'] as $display_context_id => $display_context_extra_fields) {
      foreach ($display_context_extra_fields as $extra_field_name => $extra_field_config) {
        $extra_field[$display_context_id][$extra_field_name] = $extra_field_config;
      }
    }

    return $extra_field;
  }

}
