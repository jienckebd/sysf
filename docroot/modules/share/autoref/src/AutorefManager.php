<?php

namespace Drupal\autoref;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\autoref\Plugin\autoref\AutorefPluginManager;

/**
 * Class AutorefManager.
 *
 * @package Drupal\autoref
 */
class AutorefManager {

  /**
   * @var \Drupal\bd\Entity\EntityHelper
   */
  public $entityHelper;

  /**
   * @var \Drupal\autoref\Plugin\autoref\AutorefPluginManager
   */
  public $matcherPluginManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * The current user injected into the service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $currentUser;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  public $sessionManager;

  /**
   * @var \Drupal\field\FieldConfigStorage
   */
  public $fieldConfigStorage;

  /**
   * @var \Drupal\field\FieldStorageConfigStorage
   */
  public $fieldStorageConfigStorage;

  /**
   * Sys constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(EntityHelper $entity_helper, AutorefPluginManager $matcher_plugin_manager, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AccountInterface $current_user, SessionManagerInterface $session_manager) {
    $this->entityHelper = $entity_helper;
    $this->matcherPluginManager = $matcher_plugin_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;

    $this->fieldConfigStorage = $this->entityHelper
      ->getStorage('field_config');

    $this->fieldStorageConfigStorage = $this->entityHelper
      ->getStorage('field_storage_config');
  }

  /**
   * Process auto referencing on saved entity.
   *
   * In this context, $entity is the entity being saved, $target_entity is the
   * entity with an autoref field that is eligible for auto referencing, and
   * $autoref entity is the autoref entity with any configuration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   * @param array $matcher_plugin_ids
   *   List of matcher plugins to use in processing.
   * @param int $chained_entity_depth
   *   The number of entities to search in depth.
   *
   * @return bool|mixed
   *   Whether or not values were set.
   */
  public function processEntity(EntityInterface $entity, array $matcher_plugin_ids = [], $chained_entity_depth = 0) {

    $settings = $this->configFactory->get('autoref.settings');

    // Check status of autoref settings.
    if (!$settings->get('status')) {
      return FALSE;
    }

    // Check that this entity type is configured for autoref.
    $entity_type_id = $entity->getEntityTypeId();
    $allowed_entity_types = $settings->get('entity_type');
    if (isset($allowed_entity_types[$entity_type_id])
      && ($allowed_entity_types[$entity_type_id] != $entity_type_id)) {
      return FALSE;
    }

    $matcher_plugins = [];
    if (!empty($matcher_plugin_ids)) {
      foreach ($matcher_plugin_ids as $plugin_id) {
        $matcher_plugins[] = $this->matcherPluginManager->createInstance($plugin_id);
      }
    }
    else {
      foreach ($this->matcherPluginManager->getDefinitions() as $plugin_id => $matcher_plugin_definition) {
        $matcher_plugins[] = $this->matcherPluginManager->createInstance($plugin_id);
      }
    }

    $autoref_storage = $this->entityHelper->getStorage('autoref');

    // Iterate through all autoref fields on all content entity types.
    foreach ($this->getAutorefFields() as $key => $field) {

      $autoref_field_name = $field->getName();
      $target_entity_type_id = $field->getTargetEntityTypeId();
      $target_entity_storage = $this->entityHelper->getStorage($target_entity_type_id);

      // Load all entities of the entity type having an autoref field where the
      // autoref field is populated.
      $query = $target_entity_storage
        ->getQuery();
      $query->condition($autoref_field_name, NULL, 'IS NOT NULL');
      $result = $query->execute();

      if (empty($result)) {
        continue;
      }

      $target_entities = [];
      foreach ($result as $revision_id => $entity_id) {
        $target_entities[] = $target_entity_storage->load($entity_id);
      }

      // Iterate through all autoref target entities.
      foreach ($target_entities as $key => $target_entity) {

        foreach ($target_entity->get($autoref_field_name)->getValue() as $key_inner => $data) {

          $autoref_entity = $autoref_storage->load($data['target_id']);

          // Check autoref_update and autoref_insert on autoref entity against the new state of entity.
          if (($entity->isNew() && !empty($autoref_entity->autoref_insert->value))
            || (!$entity->isNew() && !empty($autoref_entity->autoref_update->value))) {

            foreach ($autoref_entity->field_name->getValue() as $field_name_data) {

              $field_name_field = $this->fieldStorageConfigStorage->load($field_name_data['target_id']);
              $field_name = $field_name_field->getName();
              $entity_field_values = $entity->get($field_name)->getValue();

              // If this autoref is configured only to populate empty field and
              // this field is already populated, skip this autoref.
              if (($autoref_entity->autoref_empty->value == TRUE) && !empty($entity_field_values)) {
                continue;
              }

              // Check if $target_entity is already in the field values.
              foreach ($entity_field_values as $key => $data) {
                if ($data['target_id'] == $target_entity->id()) {
                  continue 2;
                }
              }

              /**
               * @var string $key
               * @var \Drupal\autoref\Plugin\autoref\matcher\MatcherInterface $matcher_plugin
               */
              foreach ($matcher_plugins as $key => $matcher_plugin) {
                $success = $matcher_plugin->matchEntity($autoref_entity, $target_entity, $entity);
                if ($success) {
                  $entity_field_values[] = [
                    'target_id' => $target_entity->id(),
                  ];
                  $entity->set($field_name, $entity_field_values);
                  continue 2;
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Build a list of autoref fields.
   *
   * @return array
   */
  public function getAutorefFields() {

    // @todo cache fields.
    $return = [];
    foreach ($this->fieldConfigStorage->loadByProperties(['field_type' => 'entity_reference']) as $key => $field) {

      // Check that the entity_reference field targets the autoref entity type.
      $autoref_entity_type = $field->getSetting('target_type');
      if ($autoref_entity_type != 'autoref') {
        continue;
      }

      // @todo allow determining order of field processing.
      if ($field->getTargetEntityTypeId() == 'taxonomy_term') {
        array_unshift($return, $field);
      }
      else {
        $return[] = $field;
      }
    }

    return $return;
  }

}
