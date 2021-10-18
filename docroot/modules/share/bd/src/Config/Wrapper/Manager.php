<?php

namespace Drupal\bd\Config\Wrapper;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides config entity wrapper management.
 */
class Manager {

  use StringTranslationTrait;

  /**
   * The entity type ID for config wrapper.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_CONFIG_WRAPPER = 'config_entity_wrapper';

  /**
   * The entity type ID for config wrapper type.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_CONFIG_WRAPPER_TYPE = 'config_entity_wrapper_type';

  /**
   * The field names to ignore for config schema type fields.
   *
   * @var array
   */
  const MAP_CONFIG_SCHEMA_FIELD_IGNORE = [
    '_core',
    'uuid',
  ];

  /**
   * The entity storage for views.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * Constructs a Manager object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity storage for views.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The factory to load a view executable with.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityHelper $entity_helper,
    TypedConfigManagerInterface $typed_config_manager,
    Connection $database,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->typedConfigManager = $typed_config_manager;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * Sync existing config entities in to config wrappers.
   *
   * @param string $config_wrapper_id
   *   The config wrapper ID.
   * @param string $entity_type_id
   *   The entity type ID of source config entities.
   *
   * @return bool
   *   Whether or not sync was made.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncAllEntityToWrapper($config_wrapper_id, $entity_type_id) {

    $entity_storage_source = $this->entityHelper->getStorage($entity_type_id);

    // Load all config entities.
    if (!$entities = $entity_storage_source->loadMultiple()) {
      return FALSE;
    }

    foreach ($entities as $entity) {
      $this->syncEntityToWrapper($entity);
    }

    return TRUE;
  }

  /**
   * Sync a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to sync.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncEntityToWrapper(EntityInterface $entity) {

    $entity_storage_config_wrapper = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_CONFIG_WRAPPER);

    $entity_id = $entity->id();
    $config_wrapper_id = $entity->getEntityTypeId();

    $entity_values_config_wrapper = [
      'bundle' => $config_wrapper_id,
    ];

    if (!$entity_config_wrapper = $entity_storage_config_wrapper->loadByProperties($entity_values_config_wrapper)) {
      $entity_config_wrapper = $entity_storage_config_wrapper->create($entity_values_config_wrapper);
    }
    else {
      $entity_config_wrapper = reset($entity_config_wrapper);
    }

    $entity_config_wrapper->set('uid', 1);

    $entity_config_wrapper->set('title', $entity->label());

    // Get all mappings.
    $mapping = $this->getConfigWrapperTypeMapping($entity->bundle());
    foreach ($mapping as $entity_wrapper_field_name => $entity_subject_field_name) {
      $entity_config_wrapper->set($entity_wrapper_field_name, $entity->get($entity_subject_field_name));
    }

    $entity_config_wrapper->save();
  }

  /**
   * Sync config wrapper to its entity subject.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_wrapper
   *   The config wrapper.
   * @param \Drupal\Core\Entity\EntityInterface $entity_subject
   *   The entity subject.
   * @param bool $save
   *   Whether or not to save the subject entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity subject.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function syncWrapperToEntity(ContentEntityInterface $entity_wrapper, EntityInterface $entity_subject, $save = TRUE) {

    // Get mapping for config wrapper.
    $mapping = $this->getConfigWrapperTypeMapping($entity_wrapper->bundle());

    $entity_type_entity_subject = $entity_subject->getEntityType();

    // Set machine name for entity subject.
    if ($entity_subject->isNew() && $entity_key_id = $entity_type_entity_subject->getKey('id')) {
      $entity_subject->set($entity_key_id, $this->getEntityIdForSubject($entity_wrapper, $entity_subject));

      // Set subject dynamic entity reference field on wrapper.
      $entity_wrapper->set('subject', [
        'target_type' => $entity_subject->getEntityTypeId(),
        'target_id' => $entity_subject->id(),
      ]);
      $entity_wrapper->save();
    }

    // Set label for entity subject.
    if ($entity_key_label = $entity_type_entity_subject->getKey('label')) {
      $entity_subject->set($entity_key_label, $entity_wrapper->label());
    }

    if ($description = $entity_wrapper->description->value) {
      $entity_subject->set('description', $description);
    }

    // Map content entity fields to config entity values.
    foreach ($mapping as $entity_wrapper_field_name => $entity_subject_field_name) {

      $field_entity_wrapper = $entity_wrapper->get($entity_wrapper_field_name);

      $value = $field_entity_wrapper->value ?: NULL;
      if (is_null($value)) {
        $value = $field_entity_wrapper->target_id;
      }

      $entity_subject->set($entity_subject_field_name, $value);
    }

    if ($save) {
      $entity_subject->save();
    }

    return $entity_subject;
  }

  /**
   * Get entity ID from a given entity wrapper for entity subject.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_wrapper
   *   The entity wrapper.
   * @param \Drupal\Core\Entity\EntityInterface $entity_subject
   *   The entity subject.
   *
   * @return string
   *   The machine name.
   */
  public function getEntityIdForSubject(ContentEntityInterface $entity_wrapper, EntityInterface $entity_subject) {

    $entity_id = $entity_wrapper->label();
    $entity_id = str_replace(' ', '_', $entity_id);
    $entity_id = strtolower($entity_id);

    return $entity_id;
  }

  /**
   * Get the field configs for a given config wrapper type.
   *
   * @param string $config_wrapper_type_id
   *   The config wrapper type ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|bool
   *   The field mapping.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getConfigWrapperTypeMapping($config_wrapper_type_id) {
    if (!$fields = $this->getConfigWrapperTypeFields($config_wrapper_type_id)) {
      return FALSE;
    }

    $mapping = [];

    foreach ($fields as $field_config_entity_id => $field) {
      if ($mapping_value = $field->getThirdPartySetting('config_entity_wrapper', 'mapping')) {
        $field_name = $field->getName();
        $mapping[$field_name] = $mapping_value;
      }
    }

    return $mapping;
  }

  /**
   * Get the field configs for a given config wrapper type.
   *
   * @param string $config_wrapper_type_id
   *   The config wrapper type ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The field configs or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getConfigWrapperTypeFields($config_wrapper_type_id) {
    return $this->entityHelper->getStorage('field_config')
      ->loadByProperties([
        'entity_type' => static::ENTITY_TYPE_ID_CONFIG_WRAPPER,
        'bundle' => $config_wrapper_type_id,
      ]);
  }

  /**
   * Load a config wrapper type.
   *
   * @param string $config_wrapper_type_id
   *   The config wrapper type ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The config wrapper type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadConfigWrapperType($config_wrapper_type_id) {
    return $this->entityHelper
      ->getStorage(static::ENTITY_TYPE_ID_CONFIG_WRAPPER_TYPE)
      ->load($config_wrapper_type_id);
  }

  /**
   * Get all config entity wrapper types.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface[]
   *   The config entity wrapper types of FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllConfigEntityWrapperType() {
    $entity_storage_config_wrapper = $this->entityHelper
      ->getStorage(static::ENTITY_TYPE_ID_CONFIG_WRAPPER_TYPE);

    if (!$entities = $entity_storage_config_wrapper->loadMultiple()) {
      return FALSE;
    }

    return $entities;
  }

  /**
   * Get config wrapper for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed|null
   *   The entity or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getWrapperForEntity(EntityInterface $entity) {
    if ($entity->isNew()) {
      return NULL;
    }

    $entities = $this->entityHelper
      ->getStorage(static::ENTITY_TYPE_ID_CONFIG_WRAPPER)
      ->loadByProperties([
        'subject.target_type' => $entity->getEntityTypeId(),
        'subject.target_id' => $entity->id(),
      ]);
    return !empty($entities) ? reset($entities) : NULL;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return |null
   */
  public function getEntityForWrapper(EntityInterface $entity) {
    if ($entity->getEntityTypeId() != 'config_entity_wrapper') {
      return NULL;
    }

    return $entity->get('subject')->entity;
  }

  /**
   * Get all typed config.
   *
   * @return mixed[]
   */
  public function getAllTypedConfig() {
    $definitions = $this->typedConfigManager->getDefinitions();
    return $definitions;
  }

  /**
   * Get all typed config.
   *
   * @return mixed[]
   */
  public function getOptionTypedConfig() {
    $definitions = $this->getAllTypedConfig();

    $options = [];

    foreach ($definitions as $id => $definition) {
      $options[$id] = $id;
    }

    return $options;
  }

  /**
   * Get config wrapper entity type for a given entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   The config wrapper entity type or FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getConfigEntityWrapperTypeForEntityType($entity_type_id) {

    $config_entity_wrapper_types = $this->getAllConfigEntityWrapperType();

    $match = FALSE;

    foreach ($config_entity_wrapper_types as $config_entity_wrapper_type) {
      if ($config_entity_wrapper_type->getThirdPartySetting('config_entity_wrapper', 'entity_type_id') == $entity_type_id) {
        $match = $config_entity_wrapper_type;
      }
    }

    return $match;
  }

  /**
   * Get field mapping of config schema type.
   *
   * @param $config_schema_type_id
   *   The config schema type.
   *
   * @return bool|array
   *   The field mapping as array or NULL.
   */
  public function getFieldOfTypedConfig($config_schema_type_id) {
    if (!$definition = $this->typedConfigManager->getDefinition($config_schema_type_id)) {
      return FALSE;
    }

    if (empty($definition['mapping'])) {
      return FALSE;
    }

    return $definition['mapping'];
  }

  /**
   * Get option set for fields of config schema type ID.
   *
   * @param string $config_schema_type_id
   *   The config schema type ID.
   *
   * @return array|bool
   *   The options array or FALSE.
   */
  public function getOptionFieldOfTypedConfig($config_schema_type_id) {
    if (!$fields = $this->getFieldOfTypedConfig($config_schema_type_id)) {
      return FALSE;
    }

    $options = [];

    foreach ($fields as $id => $field_config) {
      if (in_array($id, static::MAP_CONFIG_SCHEMA_FIELD_IGNORE)) {
        continue;
      }

      $options[$id] = !empty($field_config['label']) ? $field_config['label'] : $id;
    }

    return $options;
  }

}
