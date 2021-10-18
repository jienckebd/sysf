<?php

namespace Drupal\attribute\Plugin\attribute;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Plugin type manager for all attribute plugins.
 *
 * @ingroup attribute_plugins
 */
class PluginManager extends DefaultPluginManager {

  /**
   * Constructs a PluginManager object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $type_camelized = Container::camelize($type);
    $annotation_class = "Drupal\attribute\Annotation\Attribute{$type_camelized}";
    $interface_class = "Drupal\attribute\Plugin\attribute\\{$type}\\{$type_camelized}Interface";
    parent::__construct("Plugin/attribute/$type", $namespaces, $module_handler, $interface_class, $annotation_class);

    $this->defaults += [
      'parent' => 'parent',
      'plugin_type' => $type,
      'register_theme' => TRUE,
    ];

    $this->alterInfo('attribute_plugins_' . $type);
    $this->setCacheBackend($cache_backend, "attribute:$type");
  }

}
