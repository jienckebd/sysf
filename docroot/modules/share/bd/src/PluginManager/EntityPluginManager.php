<?php

namespace Drupal\bd\PluginManager;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Entity plugin manager.
 */
class EntityPluginManager extends DefaultPluginManager {

  /**
   * Constructs EntityPluginManager.
   *
   * @param string $provider
   *   The module provider.
   * @param string $id
   *   The plugin ID used for alter hook and cache bin.
   * @param $subnamespace
   *   Defines the namespace searched within plugin directory.
   * @param string $annotation_name
   *   The annotation string.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   */
  public function __construct(
    $provider,
    $id,
    $subnamespace,
    $annotation_name,
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {

    $annotation_class = 'Drupal\\' . $provider . '\\Annotation\\' . $annotation_name;

    parent::__construct("Plugin/{$subnamespace}", $namespaces, $module_handler, 'Drupal\bd\Plugin\EntityPluginInterface', $annotation_class);

    $this->setCacheBackend($cache_backend, $id);
    $this->alterInfo($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions();
      $this->setCachedDefinitions($definitions);
    }
    return $definitions;
  }

}
