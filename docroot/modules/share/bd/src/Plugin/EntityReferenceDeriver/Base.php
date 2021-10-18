<?php

namespace Drupal\bd\Plugin\EntityReferenceDeriver;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\bd\Plugin\PluginInterface;
use Drupal\Core\Field\PluginSettingsBase;

/**
 * Base class for deriver plugins.
 */
abstract class Base extends PluginSettingsBase implements PluginInterface {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Base constructor.
   *
   * @param array $configuration
   *   The plugin config.
   * @param $plugin_id
   *   The plugin ID.
   * @param $plugin_definition
   *   The plugin definition.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityHelper $entity_helper,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityHelper = $entity_helper;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.helper'),
      $container->get('entity_display.repository'),
      $container->get('config.factory')
    );
  }

}
