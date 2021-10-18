<?php

namespace Drupal\bd\Controller;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\Controller\EntityController as Base;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render entity routes.
 */
class EntityController extends Base {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * EntityController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, RendererInterface $renderer, TranslationInterface $string_translation, UrlGeneratorInterface $url_generator, EntityHelper $entity_helper) {
    parent::__construct($entity_type_manager, $entity_type_bundle_info, $entity_repository, $renderer, $string_translation, $url_generator);
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('renderer'),
      $container->get('string_translation'),
      $container->get('url_generator'),
      $container->get('entity.helper')
    );
  }

  /**
   * @return array
   */
  public function entityOp() {

    $arg = func_get_args();
    $build = [];

    $op_id = \Drupal::routeMatch()->getRouteObject()->getOption('_op_id');

    $entity = $this->getEntityFromRoute(\Drupal::routeMatch());

    return $this->entityHelper->getOpBuild($entity, $op_id);
  }

  /**
   * @return string
   */
  public function entityOpTitle() {

    $arg = func_get_args();

    if (!$entity = $this->getEntityFromRoute(\Drupal::routeMatch())) {
      return t('Entity Operation');
    }

    $op_id = \Drupal::routeMatch()->getRouteObject()->getOption('_op_id');
    // @todo get from entity type config title template.
    $entity_type_label = $entity->getEntityType()->getLabel();
    $entity_label = $entity->label();

    $title = "{$entity_type_label}: {$entity_label}: $op_id";

    return $title;
  }

  /**
   * Provides a page to render a single entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   The Entity to be rendered. Note this variable is named $_entity rather
   *   than $entity to prevent collisions with other named placeholders in the
   *   route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function view(EntityInterface $_entity, $view_mode = 'full') {
    $page = $this->entityHelper
      ->getViewBuilder($_entity->getEntityTypeId())
      ->view($_entity, $view_mode);

    $page['#pre_render'][] = [$this, 'buildTitle'];
    $page['#entity_type'] = $_entity->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $_entity;

    $request = \Drupal::request();
    $is_ajax = $request->isXmlHttpRequest();

    if ($is_ajax) {
      $response = new AjaxResponse();
      $title = $_entity->label();

      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($form['#attached']);

      $options = [
        'dialogClass' => 'modal-sm',
      ];

      $response->addCommand(new OpenModalDialogCommand($title, $page, $options));

      return $response;
    }

    return $page;
  }

  /**
   * Provides a page to render a single entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   The Entity to be rendered. Note this variable is named $_entity rather
   *   than $entity to prevent collisions with other named placeholders in the
   *   route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function viewDynamicEntity(EntityInterface $_entity, $view_mode = 'full') {
    $page = $this->entityManager
      ->getViewBuilder($_entity->getEntityTypeId())
      ->view($_entity, $view_mode);

    $page['#pre_render'][] = [$this, 'buildTitle'];
    $page['#entity_type'] = $_entity->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $_entity;

    return $page;
  }

  /**
   *
   */
  public function entityOpCollection() {

    // @todo sort out best way to auto build admin lists with sidebar.
    $entity_type_id = \Drupal::routeMatch()->getRouteObject()->getDefault('_entity_list');

    $build['view'] = views_embed_view("{$entity_type_id}__admin", "block_1");
    return $build;
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function templatedTitleCallback(RouteMatchInterface $route_match) {

    $route = $route_match->getRouteObject();

    $entity = $this->getEntityFromRoute($route_match, '_entity_type_id');
    $entity_type = $this->getEntityTypeFromRoute($route_match, '_entity_type_id');
    $other_entity_type = $this->getEntityTypeFromRoute($route_match, '_other_entity_type_id');
    $other_entity = $this->getEntityFromRoute($route_match, '_other_entity_type_id');

    if (!$title_template = $route->getOption('_title_template')) {
      if (!empty($entity)) {
        $label = $entity->label();
        return $label ? $this->t($label) : 'Todo no label template';
      }
      return $this->t('Entity');
    }

    $t_context = $this->entityHelper->getTContext($entity_type, $entity, $other_entity_type, $other_entity);

    return $this->t($title_template, $t_context);
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param string $route_option_key
   *
   * @return bool|mixed|null
   */
  public function getEntityFromRoute(RouteMatchInterface $route_match, $route_option_key = '_entity_type_id') {
    $route = $route_match->getRouteObject();
    if (!$entity_type_id = $route->getOption($route_option_key)) {
      return NULL;
    }

    $entity = $route_match->getParameter($entity_type_id);
    return !empty($entity) ? $entity : NULL;
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param string $route_option_key
   *
   * @return bool|\Drupal\Core\Entity\EntityTypeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityTypeFromRoute(RouteMatchInterface $route_match, $route_option_key = '_entity_type_id') {
    $route = $route_match->getRouteObject();
    if (!$entity_type_id = $route->getOption($route_option_key)) {
      return NULL;
    }

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    return !empty($entity_type) ? $entity_type : NULL;
  }

}
