<?php

namespace Drupal\bd\Sys;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;

/**
 * Class Sys.
 *
 * @package Drupal\bd\Service
 */
class Manager {

  /**
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

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
   * Sys constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(EntityHelper $entity_helper, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, AccountInterface $current_user, SessionManagerInterface $session_manager) {
    $this->entityHelper = $entity_helper;
    $this->configFactory = $config_factory;
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

}
