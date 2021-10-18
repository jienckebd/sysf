<?php

namespace Drupal\design_system\Routing;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\design_system\DesignSystem;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Alters routes for the design system.
 */
class RouteSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

  /**
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\design_system\DesignSystem $design_system
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   */
  public function __construct(
    EntityHelper $entity_helper,
    DesignSystem $design_system,
    SectionStorageManagerInterface $section_storage_manager
  ) {
    $this->entityHelper = $entity_helper;
    $this->designSystem = $design_system;
    $this->sectionStorageManager = $section_storage_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // Run after layout_builder.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -120];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
  }

}
