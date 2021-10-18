<?php

namespace Drupal\bd\Sys;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Database\Connection;
use Drupal\bd\Entity\EntityBulkUpdate;

/**
 * Class Sys.
 *
 * @package Drupal\bd\Service
 */
class Internal {

  /**
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * @var \Drupal\bd\Service\EntityBulkUpdate
   */
  public $entityBulkUpdate;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  public $database;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  public $configInstaller;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  public $state;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  public $keyValue;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

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
   * SysInternal constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\bd\Entity\EntityBulkUpdate $entity_bulk_update
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityBulkUpdate $entity_bulk_update,
    Connection $database,
    ConfigFactoryInterface $config_factory,
    ConfigInstallerInterface $config_installer,
    StateInterface $state,
    KeyValueFactoryInterface $key_value,
    ModuleHandlerInterface $module_handler,
    ModuleInstallerInterface $module_installer,
    AccountInterface $current_user,
    SessionManagerInterface $session_manager
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityBulkUpdate = $entity_bulk_update;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->configInstaller = $config_installer;
    $this->state = $state;
    $this->keyValue = $key_value;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
  }

  /**
   * @param array $config_ids
   */
  public function deleteConfig(array &$config_ids) {
    foreach ($config_ids as $config_id) {
      $config = $this->configFactory
        ->getEditable($config_id);
      if (!empty($config)) {
        $config->delete();
      }
    }
  }

  /**
   * @param array $module_ids
   */
  public function installModule(array &$module_ids) {
    $this->moduleInstaller->install($module_ids);
  }

  /**
   * @param array $module_ids
   */
  public function uninstallModule(array &$module_ids) {
    $this->moduleInstaller->uninstall($module_ids);
  }

  /**
   * Cleans deleted field data out of system.
   */
  public function cleanDeletedFields() {
    $this->state->set('field.field.deleted', NULL);
    $this->state->set('field.storage.deleted', NULL);
    $this->dropTableByStringMatch(['field_deleted%']);
  }

  /**
   * Drops a set of tables based on a set of string comparisons.
   *
   * @param array $string_comparisons
   */
  public function dropTableByStringMatch(array $string_comparisons) {
    $this->tableOpExec('drop', $string_comparisons);
  }

  /**
   * Drops a set of tables based on a set of string comparisons.
   *
   * @param array $string_comparisons
   */
  public function truncateTableByStringMatch(array $string_comparisons) {
    $this->tableOpExec('truncate', $string_comparisons);
  }

  /**
   * @param $operation
   * @param array $string_comparisons
   */
  public function tableOpExec($operation, array $string_comparisons) {

    $operation_processed = strtoupper($operation);

    foreach ($string_comparisons as $string_comparison) {
      $tables = $this->database->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.tables WHERE TABLE_NAME LIKE '{$string_comparison}'")->fetchAll();

      // Bring tables in to PHP array from SQL to allow operations per table.
      if (!empty($tables)) {
        foreach ($tables as $table_result) {
          $this->database->query($operation_processed . " TABLE {$table_result->TABLE_NAME}");
        }
      }
    }
  }

  /**
   * @param $type
   * @param $name
   */
  public function installDefaultConfig($type, $name) {
    $this->configInstaller->installDefaultConfig($type, $name);
  }

}
