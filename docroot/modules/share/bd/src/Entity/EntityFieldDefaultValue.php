<?php

namespace Drupal\bd\Entity;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Class EntityFieldDefaultValue.
 */
class EntityFieldDefaultValue {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  public $entityHelper;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  public $cache;

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(EntityHelper $entity_helper, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, AccountInterface $current_user, SessionManagerInterface $session_manager) {
    $this->entityHelper = $entity_helper;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->cache = $cache;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param \Drupal\field\FieldConfigInterface $field_config
   *
   * @return array|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function derivedDefaultValue(FieldableEntityInterface $entity, FieldConfigInterface $field_config) {

    $cache = &drupal_static(__FUNCTION__, []);

    $default_value = [];
    $default_value_config = $field_config->getThirdPartySettings('bd');

    if (empty($default_value_config['behavior']['default_value']['plugin']['plugin_id'])) {
      return $default_value;
    }

    $field_storage_config = $field_config->getFieldStorageDefinition();

    $target_entity_type_id = $field_storage_config->getSetting('target_type');

    $entity_helper = \Drupal::service('entity.helper');

    $plugin_manager_data_deriver = \Drupal::service('plugin.manager.value_provider');

    $entity_storage_default = $entity_helper->getStorage($target_entity_type_id);
    $entity_type = $entity_helper->getDefinition($target_entity_type_id);

    $entity_key_bundle = $entity_type->getKey('bundle');

    $data_deriver_plugin_id = $default_value_config['behavior']['default_value']['plugin']['plugin_id'];
    $data_deriver_plugin_config = $default_value_config['behavior']['default_value']['plugin']['plugin_configuration'];

    $plugin_data_deriver = $plugin_manager_data_deriver->createInstance($data_deriver_plugin_id, $data_deriver_plugin_config);

    if ($context_definitions = $plugin_data_deriver->getContextDefinitions()) {
      foreach ($context_definitions as $context_id => $context_definition) {
        if ($context_definition->getDataType() == 'entity') {
          $entity_context = EntityContext::fromEntity($entity);
          $plugin_data_deriver->setContext($context_id, $entity_context);
        }
      }
    }

    if ($handler_settings = $field_config->getSetting('handler_settings')) {
      if (!empty($handler_settings['target_bundles'])) {
        if (count($handler_settings['target_bundles']) == 1) {
          $default_bundle = reset($handler_settings['target_bundles']);
        }
      }
    }

    $data = [];
    $context = [];
    $context['entity'] = $entity;
    $context['field_config'] = $field_config;

    $derived = $plugin_data_deriver->getValue($entity, $field_config);
    if (empty($derived)) {
      return $default_value;
    }

    foreach ($derived as $derived_id => $derivative) {

      $default_entity_values = $derivative;

      // @todo make plugin derivers return consistent structure.
      if (!empty($default_entity_values['entity'])) {
        $default_value = $derived;
        break;
      }

      if (empty($default_entity_values[$entity_key_bundle])) {
        if (empty($default_bundle)) {
          continue;
        }
        $default_entity_values[$entity_key_bundle] = $default_bundle;
      }

      if (!isset($default_entity_values['label'])) {
        if (!empty($derivative['field_config']['label'])) {
          $default_entity_values['label'] = $derivative['field_config']['label'];
        }
      }

      $created_entity = $entity_storage_default->create($default_entity_values);

      $value_add = [
        'entity' => $created_entity,
        'target_id' => NULL,
      ];

      $default_value[] = $value_add;
    }

    return $default_value;

  }

  /**
   * @param $selected_region_id
   * @param array $all_regions
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getVariantRegion($selected_region_id, array $all_regions) {

    /** @var \Drupal\bd\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = \Drupal::service('theme.manager');
    $breakpoints = $theme_manager->getBreakpointEntityForTheme();

    $empty_region_variant = $breakpoint_variant = [];
    // Add variants for each region being empty.
    foreach ($all_regions as $region_id => $region_config) {
      if ($region_id == $selected_region_id) {
        continue;
      }

      $variant_id = "region.{$region_id}.empty";
      $empty_region_variant[] = $variant_id;
    }

    // Add variants for each breakpoint.
    if (!empty($breakpoints)) {
      foreach ($breakpoints as $breakpoint_id => $breakpoint) {
        $variant_id = "breakpoint.{$breakpoint_id}";
        $breakpoint_variant[] = $variant_id;
      }
    }

    $variant = array_merge($empty_region_variant, $breakpoint_variant);

    // Add variants for each region being empty within each breakpoint.
    foreach ($empty_region_variant as $empty_region_variant_id) {
      foreach ($breakpoint_variant as $breakpoint_id => $breakpoint) {
        $variant_id = "region_breakpoint.region.{$empty_region_variant_id}.breakpoint.{$breakpoint_id}";
        $breakpoint_variant[] = $variant_id;
      }
    }

    return $variant;
  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *
   * @return array
   */
  public static function dynamicEntityReference(FieldableEntityInterface $entity = NULL) {

    $default_value = [];

    $entity_helper = \Drupal::service('entity.helper');

    $entity_storage_default = $entity_helper->getStorage('paragraph');
    $entity_type = $entity_helper->getDefinition('paragraph');

    $entity_key_bundle = $entity_type->getKey('bundle');

    $entity_storage_deriver = $entity_helper->getStorage('paragraphs_type');

    $load_properties = [];
    if (!$default_entities = $entity_storage_deriver->loadMultiple()) {
      return NULL;
    }

    foreach ($default_entities as $entity_id_deriver => $entity_bundle) {

      $default_entity_values = [];
      $default_entity_values[$entity_key_bundle] = $entity_id_deriver;

      $entity = $entity_storage_default->create($default_entity_values);
      $default_value[] = [
        'entity' => $entity,
        'derivative' => 'todo',
      ];
    }

    return $default_value;

  }

}
