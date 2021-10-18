<?php

namespace Drupal\bd\Entity;

use Drupal\bd\Php\Arr;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bd\Config\ProcessorInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\bd\Render\Twig;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class EntityBuilder.
 */
class EntityBuilder {

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
   * The config processor.
   *
   * @var \Drupal\bd\Config\ProcessorInterface
   */
  protected $configProcessor;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

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
   * @var \Drupal\bd\Render\Twig
   */
  protected $twigRenderer;

  /**
   * The subject entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * EntityBuilder constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\bd\Config\ProcessorInterface $config_processor
   *   The config processor.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    ProcessorInterface $config_processor,
    SerializerInterface $serializer,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->configProcessor = $config_processor;
    $this->serializer = $serializer;
    $this->cache = $cache;
    $this->logger = $logger;
    $this->twigRenderer = new Twig();
  }

  /**
   * {@inheritdoc}
   */
  public function fromEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this->fromAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fromEntityId($entity_type_id, $entity_id) {
    $this->entity = $this->entityHelper
      ->getStorage($entity_type_id)
      ->load($entity_id);
    return $this->fromAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fromEntityTypeId($entity_type_id) {
    return $this->fromAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fromBundleId($entity_type_id, $bundle_id) {
    return $this->fromAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fromClone(EntityInterface $entity) {
    $this->entity = $entity->createDuplicate();
    return $this->fromAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fromCloneId($entity_type_id, $entity_id) {
    $original = $this->entityHelper
      ->getStorage($entity_type_id)
      ->load($entity_id);
    $this->entity = $original->createDuplicate();
    return $this->fromAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fromTemplate($machine_name, $variables, $reset = FALSE) {

    $entity_storage_template = $this->entityHelper->getStorage(EntityTypeBuilder::ENTITY_TYPE_ID_ENTITY_TEMPLATE);

    if (!$entity_template = $entity_storage_template->loadByProperties(['machine_name' => $machine_name])) {
      return FALSE;
    }

    $entity_template = reset($entity_template);

    /** @var \Drupal\Core\Entity\EntityInterface $entity_source */
    $entity_source = $entity_template->get('source')->entity;

    $entity_type_source = $entity_source->getEntityType();
    $entity_storage_source = $this->entityHelper->getStorage($entity_source->getEntityTypeId());

    $entity_template_source_data = $entity_source->toArray();

    $entity_template_data = $entity_template->get('data');
    $entity_template_data_variables = $entity_template_data->variables;

    if (!empty($entity_template_data_variables['entity_type_id']) && !empty($variables['entity_type_id'])) {
      $entity_type_source_variables = $this->entityHelper->getEntityTypeVariables($entity_template_data_variables['entity_type_id']);
      $entity_type_target_variables = $this->entityHelper->getEntityTypeVariables($variables['entity_type_id']);
    }

    $entity_template_data_variables_processed = [];

    foreach ($entity_type_source_variables as $key => $value) {
      if (isset($entity_type_target_variables[$key])) {
        $entity_template_data_variables_processed[$value] = $entity_type_target_variables[$key];
      }
    }

    $entity_template_source_data_processed = $entity_template_source_data;

    Arr::replacePlain($entity_template_source_data_processed, array_keys($entity_template_data_variables_processed), array_values($entity_template_data_variables_processed), TRUE);

    $entity_source_processed = $entity_storage_source->create($entity_template_source_data_processed);

    $entity_target = $entity_source_processed->createDuplicate();
    if ($entity_target instanceof ConfigEntityInterface) {
      $entity_target->set($entity_type_source->getKey('id'), $entity_source_processed->id());

      if ($entity_target_existing = $entity_storage_source->load($entity_target->id())) {
        $entity_target->set('uuid', $entity_target_existing->uuid());
        $entity_target->enforceIsNew(FALSE);
      }
    }

    $entity_target->setOriginalId($entity_target->id());

    $this->entity = $entity_target;
    return $this->fromAll();
  }

  /**
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function fromAll() {
    $this->initAll($this->entity->getEntityTypeId());
    return $this;
  }

  /**
   * @param $entity_type_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function initAll($entity_type_id = NULL) {

    if (empty($entity_type_id)) {
      $entity_type_id = $this->entity->getEntityTypeId();
    }

    $this->entityType = $this->entityHelper->getDefinition($entity_type_id);
    $this->entityStorage = $this->entityHelper->getStorage($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function save($release = TRUE) {
    $this->entity->save();
    return $this->saveAll($release);
  }

  /**
   * {@inheritdoc}
   */
  public function saveOnDestory($release = TRUE) {
    return $this->saveAll($release);
  }

  /**
   * {@inheritdoc}
   */
  public function saveOnShutdown($release = TRUE) {
    return $this->saveAll($release);
  }

  /**
   * All save methods end here.
   *
   * @param $release
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function saveAll($release) {

    $subject = $this->entity;

    if ($release) {
      unset($this->entity);
    }

    return $subject;
  }

  /**
   * @param $uuid
   *
   * @return $this
   */
  public function setUuid($uuid) {
    $load_properties = [
      'uuid' => $uuid,
    ];
    if ($existing_entity = $this->entityStorage->loadByProperties($load_properties)) {
      $existing_entity = reset($existing_entity);
      $this->entity = $existing_entity;
    }
    else {
      $this->entity->set('uuid', $uuid);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function set($field_name, $value, $delta = 0) {
    if (!$this->validate($field_name, $value)) {
      return $this;
    }

    if ($this->entity instanceof ContentEntityInterface) {
      $field = $this->entity->get($field_name);

      if (is_array($value)) {
        foreach ($value as $key => $subvalue) {
          $field->set($key, $subvalue);
        }
      }
      else {
        $field->set($delta, $value);
      }
    }
    else {
      $this->entity->set($field_name, $value);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReferenceByUuid($field_name, $value, $delta = 0, $autocreate = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function setReferenceByLabel($field_name, $value, $delta = 0, $autocreate = TRUE) {
    if (!$this->validate($field_name, $value)) {
      return $this;
    }

    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field = $this->entity->get($field_name);

    $field_definition = $field->getFieldDefinition();
    $settings = $field_definition->getSettings();

    $target_entity_type_id = $settings['target_type'];
    $target_bundle_id = reset($settings['handler_settings']['target_bundles']);

    $target_entity_storage = $this->entityHelper->getStorage($target_entity_type_id);
    $target_entity_type = $this->entityHelper->getDefinition($target_entity_type_id);
    $label_key = $target_entity_type->getKey('label');
    $bundle_key = $target_entity_type->getKey('bundle');

    $entity_values = [];
    $entity_values[$bundle_key] = $target_bundle_id;
    $entity_values[$label_key] = $value;

    if (!$target_entity = $target_entity_storage->loadByProperties($entity_values)) {
      $target_entity = $target_entity_storage->create($entity_values);
      $target_entity->save();
    }
    else {
      $target_entity = reset($target_entity);
    }

    $field->set($delta, $target_entity->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($field_name) {
    if (!$this->validate($field_name)) {
      return $this;
    }

    $field = $this->entity->get($field_name);
    $field->applyDefaultValue();

    return $this;
  }

  /**
   * @param $field_name
   * @param $value
   *
   * @return bool
   */
  protected function validate($field_name, $value = NULL) {

    if ($this->entity instanceof ContentEntityInterface) {
      if (!$this->entity->hasField($field_name)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function newFieldValues(EntityInterface $entity_a, EntityInterface $entity_b, $field_name) {
    $new_field_values = $entity_a->get($field_name)->getValue();
    $new_values = [];
    foreach ($new_field_values as $key => $field_value) {
      $new_values[$key] = $field_value['target_id'];
    }

    $original_values = [];
    $original_field_values = $entity_b->get($field_name)->getValue();
    foreach ($original_field_values as $key => $field_value) {
      $original_values[$key] = $field_value['target_id'];
    }

    return array_diff($original_values, $new_values);
  }

  /**
   * {@inheritdoc}
   */
  public function removedFieldValues(EntityInterface $entity_a, EntityInterface $entity_b, $field_name) {
    $new_field_values = $entity_a->get($field_name)->getValue();
    $new_values = [];
    foreach ($new_field_values as $key => $field_value) {
      $new_values[$key] = $field_value['target_id'];
    }

    $original_values = [];
    $original_field_values = $entity_b->get($field_name)->getValue();
    foreach ($original_field_values as $key => $field_value) {
      $original_values[$key] = $field_value['target_id'];
    }

    return array_diff($original_values, $new_values);
  }

  /**
   * {@inheritdoc}
   */
  public function hasFieldValue(EntityInterface $entity, $field_name, $value) {
  }

  /**
   *
   */
  public function ensureFieldValue($field_name, $field_value, $save = TRUE) {

    if (!$this->entity->hasField($field_name)) {
      return FALSE;
    }

    $field_items = $this->entity->get($field_name);

    $existing_values = [];
    foreach ($field_items as $delta => $field_item) {
      $existing_values[] = $field_item->target_id;
    }

    if (!in_array($field_value, $existing_values)) {
      $field_items->appendItem([
        'target_id' => $field_value,
      ]);

      if ($save) {
        $this->entity->save();
      }
    }

  }

  /**
   * @param array $match_entity_types
   *
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function cloneReferences($entity, $match_entity_types = []) {

    $this->initAll($entity->getEntityTypeId());
    $this->recurseCloneReferences($entity, $match_entity_types);

    // Return $this->fromAll();
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $match_entity_types
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function recurseCloneReferences(EntityInterface $entity, $match_entity_types = []) {

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {

      if (!in_array($field_definition->getType(), ['entity_reference', 'dynamic_entity_reference', 'entity_reference_revisions'])) {
        continue;
      }

      if ($target_type = $field_definition->getSetting('target_type')) {
        if (!in_array($target_type, $match_entity_types)) {
          continue;
        }
      }

      /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
      $field_items = $entity->get($field_name);

      if ($field_items->isEmpty()) {
        continue;
      }

      foreach ($field_items as $delta => $field_item) {

        /** @var \Drupal\Core\Entity\EntityInterface $entity_child */
        $entity_child = $field_item->entity;
        $entity_child_duplicate = $entity_child->createDuplicate();

        $this->recurseCloneReferences($entity_child_duplicate, $match_entity_types);

        $field_items->set($delta, [
          'entity' => $entity_child_duplicate,
        ]);
      }

    }

  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

}
