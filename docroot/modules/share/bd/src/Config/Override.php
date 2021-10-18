<?php

namespace Drupal\bd\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\bd\Event\ConfigOverride;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Override config values.
 */
class Override implements ConfigFactoryOverrideInterface {

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $eventDispatcher;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Indicates that the request context is set.
   *
   * @var bool
   */
  protected $contextSet;

  /**
   * ConfigOverrideEventSubscriber constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  public function __construct(
    EventDispatcherInterface $event_dispatcher,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->eventDispatcher = $event_dispatcher;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {

    $overrides = [];

    $config_names_override = [];
    $config_names_override[] = 'system.site';
    $config_names_override[] = 'drupalchat.settings';

    foreach ($names as $key => $config_name) {
      if (in_array($config_name, $config_names_override)) {
        $_ENV['SYS_RULES_TMP_CONFIG_NAME'] = $config_name;
        $event = new ConfigOverride($config_name);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(ConfigOverride::EVENT_NAME, $event);
        $event_overrides = $event->getOverrides();
        if (!empty($_ENV['SYS_RULES_TMP_CONFIG_OVERRIDES'])) {
          $overrides = NestedArray::mergeDeep($overrides, $_ENV['SYS_RULES_TMP_CONFIG_OVERRIDES']);
        }
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    if (empty($this->contextSet)) {
      $this->initiateContext();
    }
    $metadata = new CacheableMetadata();
    if (!empty($this->reactsOnDefinition)) {
      $metadata->addCacheContexts(['url.site', 'languages:language_interface', 'env']);
    }
    return $metadata;
  }

  /**
   * Set config context.
   *
   * We wait to do this in order to avoid circular dependencies
   * with the locale module.
   */
  protected function initiateContext() {
    // Prevent infinite lookups by caching the request. Since the _construct()
    // is called for each lookup, this is more efficient.
    $this->contextSet = TRUE;
    //
    //    $this->reactsOnDefinition = $this->reactsOnPluginManager->getDefinition('config_override');
  }

}
