<?php

namespace Drupal\design_system;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\bd\Entity\EntityBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bd\Config\ProcessorInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class Dom.
 */
class Dom {
  use StringTranslationTrait;

  /**
   * The entity type ID for DOM.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_DOM = 'dom';

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
   * Dom constructor.
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
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function libraryInfoBuild() {
    $info = [];

    /** @var \Drupal\design_system\Entity\Entity\Dom[] $dom_entities */
    $dom_entities = $this->entityHelper->getStorage(static::ENTITY_TYPE_ID_DOM)->loadByProperties([
      'bundle' => 'style',
    ]);

    if (empty($dom_entities)) {
      return $info;
    }

    foreach ($dom_entities as $entity_id => $entity) {

      $path_asset = $entity->getPathAsset($entity);

      $library_info = [
        'css' => [
          'theme' => [
            $path_asset => [
              'weight' => 0,
              'preprocess' => TRUE,
              'media' => TRUE,
            ],
          ],
        ],
      ];

      $library_name_suffix = $entity->getLibraryNameSuffix();
      $info[$library_name_suffix] = $library_info;

    }

    return $info;
  }

}
