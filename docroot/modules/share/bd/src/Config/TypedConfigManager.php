<?php

namespace Drupal\bd\Config;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManager as Base;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\typed_data\Widget\FormWidgetManagerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Extends core typed config manager.
 */
class TypedConfigManager extends Base implements TypedConfigManagerInterface {

  use StringTranslationTrait;

  /**
   * The typed data form widget manager.
   *
   * @var \Drupal\typed_data\Widget\FormWidgetManagerInterface
   */
  protected $typedDataFormWidgetManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Creates a new typed configuration manager.
   *
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The storage object to use for reading schema data.
   * @param \Drupal\Core\Config\StorageInterface $schemaStorage
   *   The storage object to use for reading schema data.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use for caching the definitions.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   (optional) The class resolver.
   * @param \Drupal\typed_data\Widget\FormWidgetManagerInterface $typed_data_form_widget_manager
   *   The form widget manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The config logger channel.
   */
  public function __construct(
    StorageInterface $configStorage,
    StorageInterface $schemaStorage,
    CacheBackendInterface $cache,
    ModuleHandlerInterface $module_handler,
    ClassResolverInterface $class_resolver = NULL,
    FormWidgetManagerInterface $typed_data_form_widget_manager = NULL,
    LoggerChannelInterface $logger = NULL
  ) {
    parent::__construct($configStorage, $schemaStorage, $cache, $module_handler, $class_resolver);
    $this->typedDataFormWidgetManager = $typed_data_form_widget_manager ?: \Drupal::service('plugin.manager.typed_data_form_widget');
    $this->logger = $logger ?: \Drupal::logger('config');
  }

  /**
   * {@inheritdoc}
   */
  public function createTypedDataDefinition($data_type, $config_schema, $property_name, $property_value, $parent = NULL) {

    if ($data_type == 'plugin_instance') {
      if (isset($property_value['plugin_id'])) {

        $plugin_type_id = $config_schema['plugin_type'];
        $plugin_id = $property_value['plugin_id'];
        $plugin_config = isset($property_value['plugin_config']) ? $property_value['plugin_config'] : [];

        // Can't always reliably create instance of plugin, such as field type.
        // $plugin_instance = $this->createPluginInstance($plugin_type_id, $plugin_id, $plugin_config);
        // $property_value = $plugin_manager->createInstance($plugin_id, $plugin_config);.
      }
    }

    if ($data_type == 'entity_reference') {
      $d = 1;
    }

    // $this->recurseBuildDataDefinition($config_schema, $property_value, $parent);
    $typed_config_definition = $this->getDefinition($data_type);
    $typed_config_definition = NestedArray::mergeDeep($typed_config_definition, $config_schema);
    $data_definition = $this->buildDataDefinition($typed_config_definition, $property_value, $property_name, $parent);
    $typed_data_definition = $this->create($data_definition, $property_value, $property_name, $parent);
    $typed_data_definition->setValue($property_value);
    return $typed_data_definition;
  }

  /**
   *
   */
  protected function recurseBuildDataDefinition(array $definition, $config_data, $parent) {
    foreach ($definition as $key => &$child) {
      if (is_array($child) && !empty($child['type'])) {
        $value = isset($config_data[$key]) ? $config_data[$key] : [];
        $child = $this->buildDataDefinition($child, $value, $key, $parent);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getFormWidgetForTypedDataDefinition(TypedDataInterface $typed_data_definition, array $plugin_config = []) {

    $data_definition = $typed_data_definition->getDataDefinition();

    $data_type = $data_definition->getDataType();
    if (isset(static::MAP_FORCE_DATA_TYPE_WIDGET_TYPE[$data_type])) {
      return $this->typedDataFormWidgetManager->createInstance(static::MAP_FORCE_DATA_TYPE_WIDGET_TYPE[$data_type], $plugin_config);
    }

    foreach ($this->typedDataFormWidgetManager->getDefinitions() as $plugin_id_form_widget => $plugin_definition_form_widget) {
      if (in_array($plugin_id_form_widget, ['broken'])) {
        continue;
      }

      try {
        $widget_plugin_instance = $this->typedDataFormWidgetManager->createInstance($plugin_id_form_widget, $plugin_config);

        if ($widget_plugin_instance->isApplicable($data_definition)) {
          return $widget_plugin_instance;
        }
      }
      catch (\Exception $e) {
        $this->logger->warning("todo");
      }
    }

    return FALSE;
  }

  /**
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   */
  public function validateConfigEntity(ConfigEntityInterface $entity) {
    $entity_type = $entity->getEntityType();
    $config_prefix = $entity_type->getConfigPrefix();
    $config_schema_id = "{$config_prefix}.*";
    $config_data = $entity->toArray();
    return $this->validateConfigSchema($config_schema_id, $config_data, $entity);
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigSchema($config_schema_id, $config_data, EntityInterface $entity = NULL) {
    $definition = $this->getDefinition($config_schema_id);

    if (!is_array($config_data)) {
      $config_data = [];
    }

    $violations = new ConstraintViolationList();
    $typed_data_parent = $this->createFromNameAndData($config_schema_id, $config_data);

    if (!empty($entity)) {
      $typed_data_parent->setContext('entity', $entity->getTypedData());
    }

    if (!empty($definition['mapping'])) {
      foreach ($definition['mapping'] as $property_name => $property_config) {

        $property_value = isset($config_data[$property_name]) ? $config_data[$property_name] : NULL;
        $data_type = $property_config['type'];

        $typed_data_definition = $this->createTypedDataDefinition($data_type, $property_config, $property_name, $property_value, $typed_data_parent);

        $property_violations = $typed_data_definition->validate();
        if ($property_violations->count()) {
          foreach ($property_violations as $violation) {
            $violations->add($violation);
          }
        }

      }
    }

    return new ConstraintViolationList(iterator_to_array($violations));
  }

  /**
   * {@inheritDoc}
   */
  public function createPluginInstance($plugin_type_id, $plugin_id, array $plugin_config = []) {

    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = \Drupal::service('plugin.plugin_type_manager');

    $plugin_type = $plugin_type_manager->getPluginType($plugin_type_id);

    $plugin_manager = \Drupal::service($plugin_type->getPluginManagerServiceName());

    return $plugin_manager->createInstance($plugin_id, $plugin_config);
  }

}
