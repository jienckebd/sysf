<?php

namespace Drupal\bd\Config;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\bd\Discovery\ManagerInterface;
use Drupal\bd\Php\Arr;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;

/**
 * Discovers and processes config.
 */
class Processor implements ProcessorInterface {

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface
   */
  protected $pluginTypeManager;

  /**
   * The discovery manager.
   *
   * @var \Drupal\bd\Discovery\ManagerInterface
   */
  protected $discoveryManager;

  /**
   * The discovery cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The discovery logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Processor constructor.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager
   *   The plugin type manager.
   * @param \Drupal\bd\Discovery\ManagerInterface $discovery_manager
   *   The discovery service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The discovery cache bin.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The discovery logger channel.
   */
  public function __construct(
    PluginTypeManagerInterface $plugin_type_manager,
    ManagerInterface $discovery_manager,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->pluginTypeManager = $plugin_type_manager;
    $this->discoveryManager = $discovery_manager;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function processArray(array &$array, $plugin_type_id, $plugin_id, array &$plugin_config, array &$plugin_contexts) {
    $plugin_type = $this->pluginTypeManager->getPluginType($plugin_type_id);
    $plugin_manager = \Drupal::service($plugin_type->getPluginManagerServiceName());
    $plugin_instance = $plugin_manager->createInstance($plugin_id, $plugin_config);

    $plugin_definition = $plugin_instance->getPluginDefinition();
    if (!empty($plugin_definition['required_contexts'])) {
      foreach ($plugin_definition['required_contexts'] as $required_context_id) {
        if (!isset($plugin_contexts[$required_context_id])) {
          return FALSE;
        }
      }
    }

    $plugin_instance->process($array, $plugin_contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function processConfigHook($type, array &$contexta, array &$contextb = NULL) {

    if (!$discovery_data = $this->discoveryManager->getDiscoveryData($type)) {
      return FALSE;
    }

    foreach ($discovery_data as $element_id => $element_overrides) {
      foreach ($element_overrides as $override_property => $override_property_values) {
        if (is_array($override_property_values)) {

          if (!empty($override_property_values['append'])) {
            foreach ($override_property_values['append'] as $key => $value) {
              $contexta[$element_id][$override_property][] = $value;
            }
          }

          if (!empty($override_property_values['prepend'])) {
            foreach ($override_property_values['prepend'] as $key => $value) {
              array_unshift($contexta[$element_id][$override_property], $value);
            }
          }

        }
        else {
          $contexta[$element_id][$override_property] = $override_property_values;
        }
      }
    }

  }

  /**
   * @param array $mapping
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  public function processMapping(array &$mapping, EntityTypeInterface $entity_type, EntityInterface $entity = NULL) {
    $variables = [];
    foreach ($mapping as $target_selector => $selector_config) {

      $value = NULL;
      if (is_null($selector_config)) {
        $value = NULL;
      }
      elseif (is_string($selector_config)) {
        $value = $selector_config;
      }
      elseif (is_bool($selector_config)) {
        $value = $selector_config;
      }
      elseif (is_array($selector_config)) {
        if (!empty($selector_config['plugin'])) {
          $plugin = $selector_config['plugin'];
          if ($plugin == 'entity_type_get') {
            $selector = $selector_config['selector'];

            // If dot notated, get first level with getter and then use
            // NestedArray to get inner value.
            if (stripos($selector, '.') !== FALSE) {
              $selector_parents = explode('.', $selector);

              $first_selector = array_shift($selector_parents);
              $value = $entity_type->get($first_selector);

              if (!empty($selector_parents)) {
                $value = NestedArray::getValue($value, $selector_parents);
              }

            }
            else {
              $value = $entity_type->get($selector);
            }
          }
        }
        else {
          continue;
        }
      }
      else {
        continue;
      }

      Arr::set($variables, $target_selector, $value);

    }

    // Common vars.
    $variables['entity_type_id'] = $entity_type->id();
    $variables['entity_type_label_singular'] = $entity_type->getLabel();
    $variables['entity_type_label_plural'] = $entity_type->getPluralLabel();

    return $variables;
  }

}
