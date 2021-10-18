<?php

namespace Drupal\bd\PluginManager;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Class Decorator.
 */
class Decorator {

  /**
   * The original plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $innerPluginManager;

  /**
   * The plugin manager.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->innerPluginManager = $plugin_manager;
  }

  /**
   * Extends all plugin getDefinitions() methods.
   *
   * @return mixed[]
   */
  public function getDefinitions() {
    return $this->innerPluginManager->getDefinitions();
  }

}
