<?php

namespace Drupal\bd\Entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bd\Config\ProcessorInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Class EntityLoader.
 */
class EntityLoader {
  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  protected $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The config processor.
   *
   * @var \Drupal\bd\Config\ProcessorInterface
   */
  protected $configProcessor;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * EntityLoader constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\bd\Config\ProcessorInterface $config_processor
   *   The config processor.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    ProcessorInterface $config_processor,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->configProcessor = $config_processor;
    $this->cache = $cache;
    $this->logger = $logger;
  }

}
