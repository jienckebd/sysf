<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\bd\Plugin\EntityPluginBase;
use Drupal\design_system\DesignSystem;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for render array processor plugins.
 */
abstract class Base extends EntityPluginBase {

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * LayoutBuilderComponent constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   * @param \Drupal\design_system\DesignSystem $design_system
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityHelper $entity_helper,
    TypedConfigManagerInterface $typed_config_manager,
    DesignSystem $design_system
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_helper, $typed_config_manager);
    $this->designSystem = $design_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.helper'),
      $container->get('config.typed'),
      $container->get('design.system')
    );
  }

}
