<?php

namespace Drupal\bd\Context;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Sets the default theme entity.
 */
abstract class Base implements ContextProviderInterface {
  use StringTranslationTrait;

  /**
   * The entity type helper service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The design system logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Base constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, LoggerChannelInterface $logger, TranslationInterface $string_translation) {
    $this->setStringTranslation($string_translation);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->logger = $logger;
  }

}
