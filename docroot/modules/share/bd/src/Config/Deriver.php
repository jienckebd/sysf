<?php

namespace Drupal\bd\Config;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\bd\Entity\EntityBuilder;

/**
 * Config Deriver.
 */
class Deriver implements DeriverInterface {
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
   * The entity builder.
   *
   * @var \Drupal\bd\Entity\EntityBuilder
   */
  protected $entityBuilder;

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
   * EntityTypeBuilder constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\bd\Entity\EntityBuilder $entity_builder
   *   The entity builder.
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
    EntityBuilder $entity_builder,
    ProcessorInterface $config_processor,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityBuilder = $entity_builder;
    $this->configProcessor = $config_processor;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   *
   */
  public function fromEntity($entity_type_id, $conditions) {

  }

}
