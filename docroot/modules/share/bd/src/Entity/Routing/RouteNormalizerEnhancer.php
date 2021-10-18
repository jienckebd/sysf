<?php

namespace Drupal\bd\Entity\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\bd\Entity\EntityHelper;

/**
 * Enhances and normalizes entity routes.
 */
class RouteNormalizerEnhancer implements EnhancerInterface {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   */
  public function __construct(EntityHelper $entity_helper) {
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!$this->applies($defaults[RouteObjectInterface::ROUTE_OBJECT])) {
      return $defaults;
    }

    $defaults['_entity_route'] = TRUE;

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  protected function applies(Route $route) {
    return ($route->hasOption('_field_ui'));
  }

}
