<?php

namespace Drupal\design_system\Controller;

use Drupal\Core\Entity\Controller\EntityController as Base;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses and title callbacks for entity routes.
 */
class EntityController extends Base implements ContainerInjectionInterface {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new EntityController.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityRepositoryInterface $entity_repository,
    RendererInterface $renderer,
    TranslationInterface $string_translation,
    UrlGeneratorInterface $url_generator,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($entity_helper, $entity_type_bundle_info, $entity_repository, $renderer, $string_translation, $url_generator);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.helper'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('renderer'),
      $container->get('string_translation'),
      $container->get('url_generator'),
      $container->get('current_route_match')
    );
  }

  /**
   * Generic entity title callback.
   *
   * @return string
   *   The page title.
   */
  public function genericEntityTitle(RouteMatchInterface $route_match) {
    if (!$entity = $this->getEntityFromRoute($route_match)) {
      throw new NotFoundHttpException();
    }

    if ($entity instanceof FieldableEntityInterface) {
      if ($entity->hasField('label_ia')) {
        if ($entity_ia_label = $entity->label_ia->value) {
          // Return $entity_ia_label;.
        }
      }
    }

    return $entity->label();
  }

  /**
   * Get entity from route.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   The entity or NULL.
   */
  protected function getEntityFromRoute(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    if (!$entity_type_id = $route->getOption('_entity_type_id')) {
      return FALSE;
    }
    if (!$entity = $route_match->getParameter($entity_type_id)) {
      return FALSE;
    }
    return $entity;
  }

}
