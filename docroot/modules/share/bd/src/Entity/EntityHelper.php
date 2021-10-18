<?php

namespace Drupal\bd\Entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\bd\Config\ProcessorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\bd\Config\Wrapper\Form\EntityForm;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\bd\Php\Obj;
use Drupal\bd\Php\Arr;
use Drupal\bd\Entity\Exception\InvalidEntityType;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helps entities and stuff.
 */
class EntityHelper {
  use StringTranslationTrait;

  /**
   * Sys architecture entity types.
   *
   * @var array
   */
  const TAG_SYS = [
    'app',
    'platform',
    'network',
  ];

  /**
   * Config name that stores entity type config.
   *
   * @var string
   */
  const CONFIG_NAME = "bd.entity.type";

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   * */
  protected $entityTypeManager;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config processor.
   *
   * @var \Drupal\bd\Config\ProcessorInterface
   */
  protected $configProcessor;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * The common entity type config.
   *
   * @var \Drupal\bd\Config\Config
   */
  protected $entityTypeConfig;

  /**
   * EntityHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\bd\Config\ProcessorInterface $config_processor
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    ConfigFactoryInterface $config_factory,
    ProcessorInterface $config_processor,
    Connection $database,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger,
    TranslationInterface $string_translation
  ) {
    $this->setStringTranslation($string_translation);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->cache = $cache;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->configProcessor = $config_processor;
    $this->entityTypeConfig = $this->configFactory->getConfig(static::CONFIG_NAME, NULL, FALSE);
  }

  public function getEntityFromContext($context_id = 'entity_route') {

  }

  /**
   * @return \Drupal\Core\Cache\CacheBackendInterface
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * @return \Drupal\Core\Database\Connection
   */
  public function getDatabase() {
    return $this->database;
  }

  /**
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  public function getDefinitions() {
    return $this->entityTypeManager->getDefinitions();
  }

  /**
   * @param $string
   * @param array $args
   * @param array $options
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function text($string, array $args = [], array $options = []) {
    return $this->t($string, $args, $options);
  }

  /**
   * @param $entity_type_id
   * @param bool $exception_on_invalid
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE) {
    return $this->entityTypeManager->getDefinition($entity_type_id, $exception_on_invalid);
  }

  /**
   * @return mixed
   */
  public function clearCachedDefinitions() {
    $this->entityTypeManager->clearCachedDefinitions();
  }

  /**
   * Creates a new access control handler instance.
   *
   * @param string $entity_type_id
   *   The entity type ID for this access control handler.
   *
   * @return \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   *   An access control handler instance.
   */
  public function getAccessControlHandler($entity_type_id) {
    return $this->entityTypeManager->getAccessControlHandler($entity_type_id);
  }

  /**
   * Creates a new storage instance.
   *
   * @param string $entity_type_id
   *   The entity type ID for this storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   A storage instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function getStorage($entity_type_id) {
    return $this->entityTypeManager->getStorage($entity_type_id);
  }

  /**
   * Creates a new view builder instance.
   *
   * @param string $entity_type_id
   *   The entity type ID for this view builder.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface
   *   A view builder instance.
   */
  public function getViewBuilder($entity_type_id) {
    return $this->entityTypeManager->getViewBuilder($entity_type_id);
  }

  /**
   * Creates a new entity list builder.
   *
   * @param string $entity_type_id
   *   The entity type ID for this list builder.
   *
   * @return \Drupal\Core\Entity\EntityListBuilderInterface
   *   An entity list builder instance.
   */
  public function getListBuilder($entity_type_id) {
    return $this->entityTypeManager->getListBuilder($entity_type_id);
  }

  /**
   * Creates a new form instance.
   *
   * @param string $entity_type_id
   *   The entity type ID for this form.
   * @param string $operation
   *   The name of the operation to use, e.g., 'default'.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   A form instance.
   */
  public function getFormObject($entity_type_id, $operation) {
    return $this->entityTypeManager->getFormObject($entity_type_id, $operation);
  }

  /**
   * Gets all route provider instances.
   *
   * @param string $entity_type_id
   *   The entity type ID for the route providers.
   *
   * @return \Drupal\Core\Entity\Routing\EntityRouteProviderInterface[]
   */
  public function getRouteProviders($entity_type_id) {
    return $this->entityTypeManager->getRouteProviders($entity_type_id);
  }

  /**
   * Checks whether a certain entity type has a certain handler.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param string $handler_type
   *   The name of the handler.
   *
   * @return bool
   *   Returns TRUE if the entity type has the handler, else FALSE.
   */
  public function hasHandler($entity_type_id, $handler_type) {
    return $this->entityTypeManager->hasHandler($entity_type_id, $handler_type);
  }

  /**
   * Returns a handler instance for the given entity type and handler.
   *
   * Entity handlers are instantiated once per entity type and then cached
   * in the entity type manager, and so subsequent calls to getHandler() for
   * a particular entity type and handler type will return the same object.
   * This means that properties on a handler may be used as a static cache,
   * although as the handler is common to all entities of the same type,
   * any data that is per-entity should be keyed by the entity ID.
   *
   * @param string $entity_type_id
   *   The entity type ID for this handler.
   * @param string $handler_type
   *   The handler type to create an instance for.
   *
   * @return object
   *   A handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getHandler($entity_type_id, $handler_type) {
    return $this->entityTypeManager->getHandler($entity_type_id, $handler_type);
  }

  /**
   * Creates new handler instance.
   *
   * Usually \Drupal\Core\Entity\EntityTypeManagerInterface::getHandler() is
   * preferred since that method has additional checking that the class exists
   * and has static caches.
   *
   * @param mixed $class
   *   The handler class to instantiate.
   * @param \Drupal\Core\Entity\EntityTypeInterface $definition
   *   The entity type definition.
   *
   * @return object
   *   A handler instance.
   */
  public function createHandlerInstance($class, EntityTypeInterface $definition = NULL) {
    return $this->entityTypeManager->createHandlerInstance($class, $definition);
  }

  /**
   * Rebuild definitions.
   */
  public function rebuildDefinitions() {
    $this->clearCachedDefinitions();
    $this->getDefinitions();
  }

  /**
   * @param $entity_type
   * @param $config_key
   *
   * @return array|bool|mixed
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \ReflectionException
   */
  public function getSubconfig($entity_type, $config_key) {
    $entity_type = $this->getEntityType($entity_type);
    $config_data = Obj::dismount($entity_type);
    return $this->configFactory->getSubconfigFromData($config_data, $config_key);
  }

  /**
   * @param $entity_type
   * @param $config_key
   * @param null $default_value
   *
   * @return array|bool|mixed|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \ReflectionException
   */
  public function getEntityTypeConfig($entity_type, $config_key, $default_value = NULL) {

    $entity_type = $this->getEntityType($entity_type);
    $config_data = Obj::dismount($entity_type);

    $entity_type_config = $this->configFactory->getSubconfigFromData($config_data, $config_key);
    if (!is_null($entity_type_config)) {
      return $entity_type_config;
    }

    return $default_value;
  }

  /**
   * @param $entity_type
   * @param $bundle_id
   * @param $config_key
   * @param null $default_value
   *
   * @return array|bool|mixed|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \ReflectionException
   */
  public function getBundleConfig($entity_type, $bundle_id, $config_key, $default_value = NULL) {
    $entity_type = $this->getEntityType($entity_type);
    $config_data = Obj::dismount($entity_type);

    $config_key_bundle_override = $this->getBundleConfigKey($bundle_id, $config_key);

    $bundle_overrides = $this->configFactory->getSubconfigFromData($config_data, $config_key_bundle_override);
    if (!is_null($bundle_overrides)) {
      return $bundle_overrides;
    }

    $entity_type_config = $this->configFactory->getSubconfigFromData($config_data, $config_key);
    if (!is_null($entity_type_config)) {
      return $entity_type_config;
    }

    return $default_value;

  }

  /**
   * @param $entity_type
   * @param $config_key
   * @param $config_value
   *
   * @return array|\Drupal\Core\Entity\EntityTypeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setSubconfig($entity_type, $config_key, $config_value) {
    $entity_type = $this->getEntityType($entity_type);
    return $entity_type;
  }

  /**
   * @param $entity_type
   * @param $bundle_id
   * @param $config_key
   * @param $config_value
   *
   * @return array|\Drupal\Core\Entity\EntityTypeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setBundleConfig($entity_type, $bundle_id, $config_key, $config_value) {
    $entity_type = $this->getEntityType($entity_type);

    $config_name = static::CONFIG_NAME;
    $config_key_bundle_override = $this->getBundleConfigKey($bundle_id, $config_key, $entity_type->id());
    $this->configFactory->setSubconfig($config_name, $config_key_bundle_override, $config_value);

    $this->rebuildDefinitions();

    return $entity_type;
  }

  /**
   * @param string $bundle_id
   * @param string $config_key
   * @param string|null $entity_type_id
   *
   * @return string
   */
  protected function getBundleConfigKey($bundle_id, $config_key, $entity_type_id = NULL) {
    return isset($entity_type_id) ? "{$entity_type_id}.bundle_override.{$bundle_id}.{$config_key}" : "bundle_override.{$bundle_id}.{$config_key}";
  }

  /**
   * @param null $entity_type_id
   * @param null $bundle_id
   * @param null $config_key
   *
   * @return array|\Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBundleDefinitionConfig($entity_type_id = NULL, $bundle_id = NULL, $config_key = NULL) {

    if (!empty($bundle_id)) {
      if (empty($entity_type_id)) {
        throw new \Exception("Must provide entity type if bundle is provided.");
      }
      $config_key_prefix = "{$entity_type_id}.{$bundle_id}";
    }
    else {
      // Entity type ID can either be a string or null which also works to
      // the config factory.
      $config_key_prefix = $entity_type_id;
    }

    if (!empty($config_key_prefix)) {
      $config_key = "{$config_key_prefix}.{$config_key}";
    }

    // Bundle config has different structure than entity type config.
    $config_key = "definition.{$config_key}";

    return $this->configFactory->getConfig('bd.entity.bundle', $config_key, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommonConfig($config_key = NULL) {
    $config = $this->configFactory->getConfig('bd.entity.type.common', $config_key, FALSE);
    return $config;
  }

  /**
   * @return mixed
   * @throws \Drupal\Core\Entity\Exception\EntityTypeIdLengthException
   * @throws \Drupal\bd\Entity\Exception\InvalidEntityType
   * @throws \ReflectionException
   */
  public function entityTypeAlter(array &$entity_types) {

    $this->normalizeDefinitions($entity_types);

    $entity_type_config_wrapper_type = $entity_types['config_entity_wrapper_type'];
    $config_entity_wrapper_types = $this->createHandlerInstance(
      $entity_type_config_wrapper_type->getHandlerClass('storage'),
      $entity_type_config_wrapper_type
    )->loadMultiple();

    foreach ($config_entity_wrapper_types as $entity_type_id => $entity) {
      if (empty($entity_types[$entity_type_id])) {
        $this->logger->warning("Missing entity type ID @entity_type_id.", [
          '@entity_type_id' => $entity_type_id,
        ]);
        continue;
      }

      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = &$entity_types[$entity_type_id];
      $entity_type->setFormClass('default', EntityForm::class);
      $entity_type->setFormClass('edit', EntityForm::class);
      $entity_type->setFormClass('add', EntityForm::class);
    }

  }

  /**
   * Normalize the entity type definitions.
   *
   * Entity types as arrays are referenced as $entity_type_definitions. Entity
   * types referenced as entity type objects are referenced as $entity_types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *
   * @throws \Drupal\Core\Entity\Exception\EntityTypeIdLengthException
   * @throws \Drupal\bd\Entity\Exception\InvalidEntityType
   * @throws \ReflectionException
   */
  protected function normalizeDefinitions(array &$entity_types) {

    $entity_type_config = $this->entityTypeConfig->getRawData();
    $entity_type_config_common = $this->getCommonConfig();

    // $this->attachGeneratedEntityType($entity_types, $config_entity_type_generate);

    /** @var array $entity_type_definitions */
    // Entity type objects are dismounted to arrays here. Generated entity types
    // are then attached to same array.
    $entity_type_definitions = [];

    $entity_type_templates = [];
    foreach ($entity_type_config as $key => $value) {
      if (empty($value['template'])) {
        continue;
      }
      $entity_type_templates[$key] = $value;
    }

    // Dismount objects for existing entity types in to arrays. Both generated
    // and existing entity types will then be normalized arrays.
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $definition_as_array = Obj::dismount($entity_type);
      $entity_type_definitions[$entity_type_id] = $definition_as_array;
    }

    // Add new entity type definitions.
    foreach ($entity_type_config as $generated_entity_type_id => &$generated_entity_type_definition) {

      if (empty($generated_entity_type_definition['generate'])) {
        continue;
      }

      $generated_entity_type_definition['new'] = TRUE;

      // This entity type may have been defined both in code and in UI. If so,
      // merge them giving UI version priority.
      if (!empty($entity_type_definitions[$generated_entity_type_id])) {
        $entity_type_definitions[$generated_entity_type_id] = array_replace_recursive($entity_type_definitions[$generated_entity_type_id], $generated_entity_type_definition);
      }
      else {
        $entity_type_definitions[$generated_entity_type_id] = $generated_entity_type_definition;
      }
    }

    // Add bundle entity types for created bundle entity types.
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {
      if (!empty($entity_type_definition['bundle_entity_type']) || (!empty($entity_type_definition['new']) && !empty($entity_type_definition['base']) && $entity_type_definition['base'] == 'normalized_content')) {

        if (!empty($entity_type_definition['new_bundle'])) {
          continue;
        }

        $bundle_entity_type_id = (!empty($entity_type_definition['bundle_entity_type']) && ($entity_type_definition['bundle_entity_type'] !== "{{ bundle_entity_type_id }}")) ? $entity_type_definition['bundle_entity_type'] : "{$entity_type_id}_type";

        if (empty($entity_type_definitions[$bundle_entity_type_id])) {

          // Copy normalized bundle to new entity type definition.
          $new_bundle_entity_type = $entity_type_templates['normalized_bundle'];
          $new_bundle_entity_type['new'] = TRUE;
          $new_bundle_entity_type['new_bundle'] = TRUE;

          // Make this the bundle of the original entity type.
          $new_bundle_entity_type['bundle_of'] = $entity_type_id;

          // Set label for bundle entity type.
          $new_bundle_entity_type['label'] = "{$entity_type_definition['label']} Type";
          $new_bundle_entity_type['label_plural'] = "{$entity_type_definition['label']} Types";

          $entity_type_definitions[$bundle_entity_type_id] = $new_bundle_entity_type;
        }

      }
    }

    // Merge base entity types/templates.
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {
      if (empty($entity_type_definition['base'])) {
        continue;
      }
      $this->recurseMergeBaseEntityType($entity_type_definitions, $entity_type_templates, $entity_type_definition, $entity_type_definition['base']);
    }

    $this->attachStandardTags($entity_type_definitions);

    // Needs to go before variable replacements to support variables in alter.
    foreach ($entity_type_config as $entity_type_id => $entity_type_alter_config) {

      if (empty($entity_type_alter_config['alter'])) {
        continue;
      }

      // Altering entity types also supports by subset type.
      if (stripos($entity_type_id, 'tag__') === 0) {
        $tag_id = str_replace('tag__', '', $entity_type_id);
        foreach ($entity_type_definitions as $subset_entity_type_id => &$current_entity_type_definition) {

          $entity_type_tags = !empty($current_entity_type_definition['tag']) ? $current_entity_type_definition['tag'] : [];

          if (in_array($tag_id, $entity_type_tags)) {
            $current_entity_type_definition = NestedArray::mergeDeep($current_entity_type_definition, $entity_type_alter_config);
          }
        }
      }
      else {

        if (empty($entity_type_definitions[$entity_type_id])) {
          $this->logger->warning("Missing entity type to alter: @entity_type_id", [
            '@entity_type_id' => $entity_type_id,
          ]);
          continue;
        }

        if (!empty($entity_type_alter_config['from'])) {
          $from_entity_type_id = $entity_type_alter_config['from'];
          if (!empty($config_entity_type_alter[$from_entity_type_id])) {
            $entity_type_alter_config = NestedArray::mergeDeep($entity_type_alter_config[$from_entity_type_id], $entity_type_alter_config);
          }
        }

        $current_entity_type_definition = &$entity_type_definitions[$entity_type_id];
        $current_entity_type_definition = NestedArray::mergeDeep($current_entity_type_definition, $entity_type_alter_config);
      }

    }

    // Process links.
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {

      // @todo sort out field_config not having canonical template.
      if (!empty($entity_type_definition['base'])) {
        if (empty($entity_type_definition['links']['canonical']) || ($entity_type_definition['links']['canonical'] == "{{ path__canonical }}")) {
          $entity_type_definition['links']['canonical'] = "/{$entity_type_id}/{{$entity_type_id}}";
        }
      }

      if (empty($entity_type_definition['links']['collection']) || ($entity_type_definition['links']['collection'] == "{{ path__collection }}")) {
        if (!empty($entity_type_definition['bundle_entity_type'])) {
          $entity_type_definition['links']['collection'] = "/admin/structure/{$entity_type_id}";
        }
      }

    }

    // Process bundle entity types in second pass so path can be appended to the
    // entity type it bundles.
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {
      if (empty($entity_type_definition['new'])) {
        continue;
      }

      if (empty($entity_type_definition['links']['collection']) || ($entity_type_definition['links']['collection'] == "{{ path__collection }}")) {
        if (!empty($entity_type_definition['bundle_of'])) {
          if (empty($entity_type_definitions[$entity_type_definition['bundle_of']]['links']['collection'])) {
            continue;
          }
          $bundle_of_collection_path = $entity_type_definitions[$entity_type_definition['bundle_of']]['links']['collection'];
          $entity_type_definition['links']['collection'] = "{$bundle_of_collection_path}/type";
        }
      }

    }

    // Derive config from entity type tags.
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {
      if (empty($entity_type_definition['tag'])) {
        continue;
      }

      foreach ($entity_type_definition['tag'] as $tag) {

        switch ($tag) {

          case 'admin':

            // Make admin routes.
            $entity_type_definition['menu']['route']['*']['option']['_admin_route'] = TRUE;

            // Make canonical base path relative to collection path.
            $entity_type_definition['links']['canonical'] = "/admin/structure/{$entity_type_id}/manage/{{$entity_type_id}}";

            break;

          default:
            break;

        }

      }

    }

    // Replace variables.
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {

      $bundle_entity_type_id = NULL;
      if (!empty($entity_type_definition['bundle_entity_type'])) {
        if ($entity_type_definition['bundle_entity_type'] !== "{{ bundle_entity_type_id }}") {
          $bundle_entity_type_id = $entity_type_definition['bundle_entity_type'];
        }
        else {
          $bundle_entity_type_id = "{$entity_type_id}_type";
        }
      }

      $var = [
        'entity_type_id' => $entity_type_id,
        'bundle_entity_type_id' => $bundle_entity_type_id,
        'entity_type_label_singular' => !empty($entity_type_definition['label']) ? $entity_type_definition['label'] : $entity_type_id,
        'entity_type_label_plural' => !empty($entity_type_definition['label_plural']) ? $entity_type_definition['label_plural'] : $entity_type_id,
        'path__canonical' => !empty($entity_type_definition['links']['canonical']) ? $entity_type_definition['links']['canonical'] : "",
        'path__collection' => !empty($entity_type_definition['links']['collection']) ? $entity_type_definition['links']['collection'] : "",
      ];

      if (!empty($entity_type_definition['bundle_of'])) {
        $var['bundle_of_entity_type_id'] = $entity_type_definition['bundle_of'];
      }

      Arr::replace($entity_type_definition, array_keys($var), array_values($var));

    }

    // Make strings translatable by their keys of array.
    $translation_key = !empty($entity_type_config_common['translation']['key']['singular']) ? $entity_type_config_common['translation']['key']['singular'] : [];
    $translation_plural_key = !empty($entity_type_config_common['translation']['key']['plural']) ? $entity_type_config_common['translation']['key']['plural'] : [];

    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {
      foreach ($entity_type_definition as $key => &$value) {
        if (empty($value) || is_object($value)) {
          continue;
        }

        if (in_array($key, $translation_key)) {
          $value = $this->t($value);
        }
        elseif (in_array($key, $translation_plural_key)) {
          $value = new PluralTranslation($value);
        }

      }
    }

    // Re-sort final entity types alphabetically.
    ksort($entity_type_definitions);

    // Mount objects.
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {
      if (!empty($entity_type_definition['base_table']) || !empty($entity_type_definition['bundle_entity_type']) || (!empty($entity_type_definition['handlers']['storage']) && $entity_type_definition['handlers']['storage'] == ContentEntityNullStorage::class)) {
        $entity_types[$entity_type_id] = new ContentEntityType($entity_type_definition);
      }
      else {
        $entity_types[$entity_type_id] = new ConfigEntityType($entity_type_definition);
      }

    }

  }

  /**
   * Attach standard tags to all entity types.
   *
   * @param array $entity_type_definitions
   */
  protected function attachStandardTags(array &$entity_type_definitions) {
    foreach ($entity_type_definitions as $entity_type_id => &$entity_type_definition) {

      if (!empty($entity_type_definition['base_table'])) {
        $entity_type_definition['tag'][] = 'content';
      }
      else {
        $entity_type_definition['tag'][] = 'config';
      }

      if (!empty($entity_type_definition['bundle_of'])) {
        $entity_type_definition['tag'][] = 'bundle';
      }

      if (!empty($entity_type_definition['links']['canonical'])) {
        $entity_type_definition['tag'][] = 'path__canonical';
      }

      if (!empty($entity_type_definition['field_ui_base_route'])) {
        $entity_type_definition['tag'][] = 'display';
      }
      else {
        $entity_type_definition['tag'][] = 'nodisplay';
      }

      if (in_array($entity_type_id, static::TAG_SYS)) {
        $entity_type_definition['tag'][] = 'sys';
      }

    }
  }

  /**
   * @param $entity_type_id
   * @param array $data
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function recurseReplaceEntityTypeVar($entity_type_id, array &$data) {
    $var = $this->getEntityTypeVariables($entity_type_id);
    Arr::replace($data, array_keys($var), array_values($var));
  }

  /**
   * @param $entity_type_id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityTypeVariables($entity_type_id) {

    $entity_type = $this->getDefinition($entity_type_id);

    return [
      'entity_type_id' => $entity_type_id,
      'bundle_entity_type_id' => $entity_type->getBundleEntityType(),
      'entity_type_label_singular' => $entity_type->getLabel()->__toString(),
      'entity_type_label_plural' => $entity_type->getPluralLabel()->__toString(),
      'path__canonical' => $entity_type->getLinkTemplate('canonical'),
      'path__collection' => $entity_type->getLinkTemplate('collection'),
      'bundle_of_entity_type_id' => $entity_type->getBundleOf(),
    ];
  }

  /**
   * @param array $entity_type_definitions
   * @param array $entity_type_templates
   * @param array $entity_type_definition
   * @param string $base_entity_type_id
   *
   * @throws \Drupal\bd\Entity\Exception\InvalidEntityType
   */
  protected function recurseMergeBaseEntityType(array &$entity_type_definitions, array &$entity_type_templates, array &$entity_type_definition, string $base_entity_type_id) {
    if (empty($entity_type_definition['base'])) {
      return;
    }

    // Check if base is a template or an existing entity type.
    if (!empty($entity_type_templates[$base_entity_type_id])) {
      $base_entity_type = $entity_type_templates[$base_entity_type_id];
    }
    elseif (!empty($entity_type_definitions[$base_entity_type_id])) {
      $base_entity_type = $entity_type_definitions[$base_entity_type_id];
    }
    else {
      throw new InvalidEntityType("Base entity type {$base_entity_type_id} is not an existing entity type or template.");
    }

    $entity_type_definition = array_replace_recursive($base_entity_type, $entity_type_definition);

    if (!empty($base_entity_type['base'])) {
      $subbase_entity_type_id = $base_entity_type['base'];
      $this->recurseMergeBaseEntityType($entity_type_definitions, $entity_type_templates, $entity_type_definition, $subbase_entity_type_id);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   * @param array $config_entity_type_generate
   */
  protected function attachGeneratedEntityType(array &$entity_types, array &$config_entity_type_generate) {

    $table_name_entity_type_index = 'entity_type_field_data';
    if (!$this->database->schema()->tableExists($table_name_entity_type_index)) {
      return;
    }

    $entity_type_definition_generate = $this->database
      ->select($table_name_entity_type_index, "fi")
      ->fields('fi', ['data_index'])
      ->execute()
      ->fetchAll();

    $mapping = [
      'id' => 'field_machine_name.0.value',
      'label' => 'field_label.0.value',
      'label_plural' => 'field_label_plural.0.value',
      'tag' => 'field_tags',
    ];

    foreach ($entity_type_definition_generate as $key => $result) {

      $definition = [];
      $definition['base'] = 'normalized_content';

      $index = unserialize($result->data_index) ?: [];

      foreach ($mapping as $entity_type_definition_key => $selector) {
        $parents = explode('.', $selector);
        $value_raw = NestedArray::getValue($index, $parents);
        if (is_null($value_raw)) {
          continue;
        }

        $value = NULL;
        if (is_array($value_raw) && !empty($value_raw[0]['label'])) {
          foreach ($value_raw as $delta => $field_item_values) {
            $value[] = $field_item_values['label'];
          }
        }
        else {
          $value = $value_raw;
        }

        $definition[$entity_type_definition_key] = $value;
      }

      $entity_type_id = $definition['id'];

      // This entity type may have been defined both in YAML and in UI. If so,
      // merge them giving UI version priority.
      if (!empty($config_entity_type_generate[$entity_type_id]['definition'])) {
        $config_entity_type_generate[$entity_type_id]['definition'] = array_replace_recursive($config_entity_type_generate[$entity_type_id]['definition'], $definition);
      }
      else {
        $config_entity_type_generate[$entity_type_id]['definition'] = $definition;
      }

    }
  }

  /**
   * @param $entity_type_definition
   *
   * @return array|\Drupal\Core\Entity\EntityTypeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityType($entity_type_definition) {
    if (is_string($entity_type_definition)) {
      $entity_type_definition = $this->getDefinition($entity_type_definition);
    }
    return $entity_type_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByTag($tag) {

    $return = [];

    $entity_types = $this->getDefinitions();

    foreach ($entity_types as $entity_type_id => $entity_type) {

      $entity_type_tags = $entity_type->get('tag') ?: [];
      if (in_array($tag, $entity_type_tags)) {
        $return[$entity_type_id] = $entity_type;
      }

    }

    return $return;
  }

  /**
   * @param $entity_type_definition
   *
   * @return false|mixed
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOriginalDefinition($entity_type_definition) {
    if (is_string($entity_type_definition)) {
      $entity_type_definition = $this->getDefinition($entity_type_definition);
    }
    return $entity_type_definition->get('original_definition') ?: FALSE;
  }

  /**
   * @param $entity_type_definition
   * @param $handler_type
   *
   * @return false|object
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOriginalHandler($entity_type_definition, $handler_type) {

    if (!$original_definition = $this->getOriginalDefinition($entity_type_definition)) {
      return FALSE;
    }

    if (empty($original_definition['handler_classes'][$handler_type])) {
      return FALSE;
    }

    return $this->createHandlerInstance($original_definition['handler_classes'][$handler_type], $entity_type_definition);
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   * @param \Drupal\Core\Entity\EntityTypeInterface|null $other_entity_type
   * @param \Drupal\Core\Entity\EntityInterface|null $other_entity
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTContext(EntityTypeInterface $entity_type, EntityInterface $entity = NULL, EntityTypeInterface $other_entity_type = NULL, EntityInterface $other_entity = NULL) {
    $context = [];

    $context['@entity_type_label_singular'] = $entity_type->getLabel();
    $context['@entity_type_label_plural'] = $entity_type->getPluralLabel();

    if (!empty($entity)) {
      $context['@entity_label'] = $entity->label();

      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      $bundle_id = $entity->bundle();

      if (!empty($bundle_entity_type_id) && !empty($bundle_id)) {
        $entity_bundle = $this->getStorage($bundle_entity_type_id)->load($bundle_id);
        $context['@entity_type_or_bundle_label_singular'] = $entity_bundle->label();
      }
      else {
        $context['@entity_type_or_bundle_label_singular'] = $context['@entity_type_label_singular'];
      }
    }

    if (empty($context['@entity_type_or_bundle_label_singular'])) {
      $context['@entity_type_or_bundle_label_singular'] = $context['@entity_type_label_singular'];
    }

    if (!empty($other_entity_type)) {
      $context['@other_entity_type_label_singular'] = $other_entity_type->getLabel();
      $context['@other_entity_type_label_plural'] = $other_entity_type->getPluralLabel();
    }

    if (!empty($other_entity)) {
      $context['@other_entity_label'] = $other_entity->label();
    }

    return $context;
  }

  /**
   * @param $entity_type
   * @param $bundle_id
   *
   * @return \Drupal\Core\Entity\EntityInterface|false|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityTypeBundle($entity_type, $bundle_id) {
    $entity_type = $this->getEntityType($entity_type);

    if (!$bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      return FALSE;
    }

    $entity_storage_bundle = $this->getStorage($bundle_entity_type_id);

    if (!$entity_bundle = $entity_storage_bundle->load($bundle_id)) {
      return FALSE;
    }

    return $entity_bundle;
  }

  /**
   * @param $entity_type
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|false
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityTypeBundleList($entity_type) {
    $entity_type = $this->getEntityType($entity_type);

    if (!$bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      return FALSE;
    }

    $entity_storage_bundle = $this->getStorage($bundle_entity_type_id);

    if (!$entity_bundle_list = $entity_storage_bundle->loadMultiple()) {
      return FALSE;
    }

    return $entity_bundle_list;
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param null $op_id
   *
   * @return false|mixed
   */
  public function getOpConfig(EntityTypeInterface $entity_type, $op_id = NULL) {
    if (!$op_config_all = $entity_type->get('op')) {
      return FALSE;
    }

    if (empty($op_id)) {
      return $op_config_all;
    }

    if (empty($op_config_all[$op_id])) {
      return FALSE;
    }

    return $op_config_all[$op_id];
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param $op_id
   *
   * @return false|mixed|string
   */
  public function getOpPath(EntityTypeInterface $entity_type, $op_id) {
    if (!$op_config = $this->getOpConfig($entity_type, $op_id)) {
      return FALSE;
    }

    $entity_type_id = $entity_type->id();

    if (!empty($op_config['path'])) {
      $path = $op_config['path'];
    }
    else {
      // Get canonical path.
      if ($canonical_path = $entity_type->getLinkTemplate('canonical')) {
        // Append op id to canonical route.
        $path = "{$canonical_path}/{$op_id}";
      }
      else {
        $path = "/{$entity_type_id}/{{$entity_type_id}}/{$op_id}";
      }
    }

    return $path;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $op_id
   *
   * @return array
   */
  public function getOpBuild(EntityInterface $entity, $op_id) {

    $build = [];

    if (!$op_config = $this->getOpConfig($entity->getEntityType(), $op_id)) {
      return $build;
    }

    if (!empty($op_config['plugin_config']['entity_selector'])) {
      $entity_to_use = $entity->get($op_config['plugin_config']['entity_selector'])->entity;
    }
    else {
      $entity_to_use = $entity;
    }

    if ($op_config['plugin'] = 'entity_form') {
      $build = \Drupal::service('entity.form_builder')->getForm($entity_to_use, $op_config['plugin_config']['op']);
    }

    return $build;
  }

}
