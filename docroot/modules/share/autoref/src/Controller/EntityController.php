<?php

namespace Drupal\autoref\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\Controller\EntityController as Base;

/**
 * Returns responses for entity routes.
 */
class EntityController extends Base implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  public $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  public $entityFormBuilder;

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
   */
  public function __construct(EntityHelper $entity_helper, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, RendererInterface $renderer, TranslationInterface $string_translation, UrlGeneratorInterface $url_generator, RouteMatchInterface $route_match, EntityFormBuilderInterface $entity_form_builder) {
    $this->entityHelper = $entity_helper;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityRepository = $entity_repository;
    $this->renderer = $renderer;
    $this->stringTranslation = $string_translation;
    $this->urlGenerator = $url_generator;
    $this->routeMatch = $route_match;
    $this->entityFormBuilder = $entity_form_builder;
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
      $container->get('current_route_match'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function add() {

    $route = $this->routeMatch->getRouteObject();
    $options = $route->getOptions();

    // This route is made generic for all entity types by defining an
    // "_entity_type_id" option on the route object.
    $entity_type_id = $options['_entity_type_id'];
    $bundle_param_id = "{$entity_type_id}_type";
    if (!$bundle_id = $this->routeMatch->getParameter($bundle_param_id)) {
      $bundle_id = 'default';
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityHelper
      ->getStorage($entity_type_id)
      ->create([
        'type' => $bundle_id,
      ]);

    $form = $this->entityFormBuilder->getForm($entity);

    return $form;
  }

}
