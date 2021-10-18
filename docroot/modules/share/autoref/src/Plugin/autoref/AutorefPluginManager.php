<?php

namespace Drupal\autoref\Plugin\autoref;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Plugin type manager for all autoref plugins.
 *
 * @ingroup autoref_plugins
 */
class AutorefPluginManager extends DefaultPluginManager {

  /**
   * Constructs a AutorefPluginManager object.
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
    $annotation_class = "Drupal\autoref\Annotation\Autoref{$type_camelized}";
    $interface_class = "Drupal\autoref\Plugin\autoref\\{$type}\\{$type_camelized}Interface";
    parent::__construct("Plugin/autoref/$type", $namespaces, $module_handler, $interface_class, $annotation_class);

    $this->defaults += [
      'parent' => 'parent',
      'plugin_type' => $type,
      'register_theme' => TRUE,
    ];

    $this->alterInfo('autoref_plugins_' . $type);
    $this->setCacheBackend($cache_backend, "autoref:$type");
  }

}
