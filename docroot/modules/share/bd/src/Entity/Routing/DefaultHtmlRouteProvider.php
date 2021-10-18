<?php

namespace Drupal\bd\Entity\Routing;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity\Routing\DefaultHtmlRouteProvider as Base;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Drupal\bd\Controller\EntityController;

/**
 * Extends entity in contrib route provider for normal entities.
 */
class DefaultHtmlRouteProvider extends Base {

  /**
   * The entity type helper.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityHelper;

  /**
   * DefaultHtmlRouteProvider constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityHelper $entity_helper) {
    parent::__construct($entity_type_manager, $entity_field_manager);
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    if ($entity_type->id() == 'behavior_type') {
      $d = 1;
    }
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();
    $admin_route_names = [
      "entity.{$entity_type_id}.add_page",
      "entity.{$entity_type_id}.add_form",
      "entity.{$entity_type_id}.edit_form",
      "entity.{$entity_type_id}.delete_form",
      "entity.{$entity_type_id}.delete_multiple_form",
      "entity.{$entity_type_id}.duplicate_form",
    ];
    foreach ($admin_route_names as $admin_route_name) {
      if ($route = $collection->get($admin_route_name)) {
        $route->setOption('_admin_route', TRUE);
      }
    }

    if ($op_config_all = $this->entityHelper->getOpConfig($entity_type)) {

      $entity_type_id = $entity_type->id();

      foreach ($op_config_all as $op_id => &$op_config) {

        // @todo use entity operation plugin.
        if ($route = $this->buildEntityOpRoute($entity_type, $op_id, $op_config)) {
          $route_name = "entity.{$entity_type_id}.{$op_id}";
          $collection->add($route_name, $route);
        }
      }
    }

    return $collection;
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param $op_id
   * @param $op_config
   *
   * @return \Symfony\Component\Routing\Route|bool
   */
  protected function buildEntityOpRoute(EntityTypeInterface $entity_type, $op_id, $op_config) {
    $entity_type_id = $entity_type->id();

    if (!$op_path = $this->entityHelper->getOpPath($entity_type, $op_id)) {
      return FALSE;
    }

    $route = new Route($op_path);
    $route
      ->setDefaults([
        '_controller' => EntityController::class . '::entityOp',
        '_title_callback' => EntityController::class . '::entityOpTitle',
        'entity_type_id' => $entity_type_id,
      ])
      ->setRequirement('_entity_access', "{$entity_type_id}.{$op_id}")
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);

    $route->setOption('_entity_type_id', $entity_type_id);
    $route->setOption('_op_id', $op_id);

    if (!empty($op_config['route']['options']['add'])) {
      $options = $route->getOptions();
      foreach ($op_config['route']['options']['add'] as $key => $value) {
        $options[$key] = $value;
      }
      $route->setOptions($options);
    }

    if (!empty($op_config['route']['defaults']['add'])) {
      $defaults = $route->getDefaults();
      foreach ($op_config['route']['defaults']['add'] as $key => $value) {
        if (isset($defaults[$key]) && is_array($defaults[$key])) {
          $defaults[$key] = NestedArray::mergeDeep($defaults[$key], $value);
        }
        else {
          $defaults[$key] = $value;
        }
      }
      $route->setDefaults($defaults);
    }

    if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
      $route->setRequirement($entity_type_id, '\d+');
    }

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    if ($route && $entity_type instanceof ContentEntityTypeInterface) {
      $route->setDefault('_controller', '\Drupal\bd\Controller\EntityController::entityOpCollection');
      if ($route->hasOption('_entity_list')) {
        $route->setOption('_entity_list', NULL);
      }
    }
    return $route;
  }

}
