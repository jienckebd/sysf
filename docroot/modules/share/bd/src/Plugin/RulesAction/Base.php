<?php

namespace Drupal\bd\Plugin\RulesAction;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base action for rules action plugins.
 */
class Base extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Base constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityHelper $entity_helper, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityHelper = $entity_helper;
    $this->logger = $logger;
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
      $container->get('logger.channel.design_system')
    );
  }

}
