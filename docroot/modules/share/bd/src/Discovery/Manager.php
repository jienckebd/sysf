<?php

namespace Drupal\bd\Discovery;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Class Manager.
 */
class Manager implements ManagerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * Manager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The discovery cache bin.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The discovery logger channel.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->moduleHandler = $module_handler;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiscoveryData($discovery_type, $flatten = TRUE, $reset = FALSE) {
    $cid = "bd.discovery.data.{$discovery_type}";

    if ($reset) {
      $this->cache->invalidate($cid);
    }

    if ($data = $this->cache->get($cid)) {
      return $data->data;
    }

    $discovery_data = [];
    foreach ($this->moduleHandler->getModuleDirectories() as $module_name => $module_directory) {

      $file_path = "{$module_directory}/{$module_name}.{$discovery_type}.yml";

      if (!file_exists($file_path)) {
        continue;
      }

      if (!$decoded_data = Yaml::decode(file_get_contents($file_path))) {
        continue;
      }

      if ($flatten == TRUE) {
        // If flattened array, attach data directly to returned array.
        foreach ($decoded_data as $key => $value) {
          $discovery_data[$key] = $value;
        }
      }
      else {
        $discovery_data[$module_name] = $decoded_data;
      }
    }

    $this->cache->set($cid, $discovery_data);

    return $discovery_data;
  }

}
