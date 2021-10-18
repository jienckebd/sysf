<?php

namespace Drupal\attribute;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class AttributeManager.
 *
 * @package Drupal\attribute
 */
class AttributeManager {

  /**
   * @var \Drupal\bd\Entity\EntityHelper
   */
  public $entityHelper;

  /**
   * @var \Drupal\attribute\Plugin\attribute\PluginManager
   */
  public $matcherPluginManager;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  public $cache;

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
   * AttributeManager constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\attribute\Plugin\attribute\PluginManager $matcher_plugin_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(EntityHelper $entity_helper, CacheBackendInterface $cache, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AccountInterface $current_user, SessionManagerInterface $session_manager) {
    $this->entityHelper = $entity_helper;
    $this->cache = $cache;
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
   * Build a list of attribute fields.
   *
   * @return array
   */
  public function getAttributeFields() {

    $cid = 'attribute_field_list';
    $data = $this->cache->get($cid);

    if (empty($data)) {
      $data = [];
      /**
       * @var string $key
       * @var \Drupal\field\FieldConfigInterface $field
       */
      foreach ($this->fieldConfigStorage->loadByProperties(['field_type' => 'entity_reference']) as $key => $field) {

        $entity_type = $field->getTargetEntityTypeId();
        $bundle = $field->getTargetBundle();
        $field_name = $field->getName();

        // Check that the entity_reference field targets the attribute entity type.
        $attribute_entity_type = $field->getSetting('target_type');
        if ($attribute_entity_type != 'attribute') {
          continue;
        }

        $data[$entity_type][$bundle][$field_name] = $field;
      }

      $this->cache->set($cid, $data);
    }
    else {
      $data = $data->data;
    }

    return $data;
  }

}
