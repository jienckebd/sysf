<?php

namespace Drupal\bd\PluginType;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Provides a plugin type manager.
 */
class Manager {

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface
   */
  protected $pluginTypeManager;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface
   */
  protected $pluginSelectorManager;

  /**
   * The plugin logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Manager constructor.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface $plugin_selector_manager
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   */
  public function __construct(
    PluginTypeManagerInterface $plugin_type_manager,
    PluginSelectorManagerInterface $plugin_selector_manager,
    LoggerChannelInterface $logger,
    CacheBackendInterface $cache
  ) {
    $this->pluginTypeManager = $plugin_type_manager;
    $this->pluginSelectorManager = $plugin_selector_manager;
    $this->logger = $logger;
    $this->cache = $cache;
  }

  /**
   * @param $plugin_type_id
   * @param $label
   * @param $description
   * @param $required
   *
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   */
  public function getPluginSelector($plugin_type_id, $label = NULL, $description = NULL, $required = FALSE) {

    $plugin_type = $this->pluginTypeManager->getPluginType($plugin_type_id);

    $plugin_selector = $this->pluginSelectorManager->createInstance($plugin_type_id);
    $plugin_selector->setLabel($label);
    $plugin_selector->setDescription($description);
    $plugin_selector->setRequired($required);
    $plugin_selector->setSelectablePluginType($plugin_type);
    $plugin_selector->setKeepPreviouslySelectedPlugins();

    return $plugin_selector;
  }

}
