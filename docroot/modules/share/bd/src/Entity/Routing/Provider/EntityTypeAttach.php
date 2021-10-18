<?php

namespace Drupal\bd\Entity\Routing\Provider;

use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Attaches entity routes to other entities.
 */
class EntityTypeAttach implements EntityRouteProviderInterface, EntityHandlerInterface {

  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new DefaultHtmlRouteProvider.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityHelper $entity_helper, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity.helper'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {

    $collection = new RouteCollection();

    $entity_type_id = $entity_type->id();
    $entity_type_label = $entity_type->getLabel();

    foreach ($this->entityHelper->getDefinitionsByTag('display') as $other_entity_type_id => $other_entity_type) {

      if (!$admin_permission = $entity_type->get('admin_permission')) {
        continue;
      }

      $bundle_entity_type_id = $other_entity_type->getBundleEntityType();
      $field_ui_base_route = $other_entity_type->get('field_ui_base_route');

      $route_provider = \Drupal::service('router.route_provider');
      try {
        $base_route = $route_provider->getRouteByName($field_ui_base_route);
      }
      catch (\Exception $e) {
        \Drupal::logger('entity')->warning("Route doesn't exist: @route_name", [
          '@route_name' => $field_ui_base_route,
        ]);
        continue;
      }
      $base_route_options = $base_route->getOptions();
      $base_path = $base_route->getPath();

      $entity_type_id_friendly = str_replace('_', '-', $entity_type_id);
      $path = "{$base_path}/fields/add-{$entity_type_id_friendly}";

      $route = new Route($path);
      $route->setDefault('_title', $this->t('Add @entity_type_label_singular', [
        '@entity_type_label_singular' => $entity_type_label,
      ])->__toString());
      $route->setDefault('_entity_form', "{$entity_type_id}.default");
      $route->setRequirement('_permission', $admin_permission);
      $route->setOptions($base_route_options);

      $route->setOption('_entity_type_id', $entity_type_id);
      $route->setOption('_other_entity_type_id', $bundle_entity_type_id);
      $route->setOption('_title_template', 'Add @entity_type_label_singular to @other_entity_type_label_singular @other_entity_label');

      $route_name = "entity.{$other_entity_type_id}.fields.add.{$entity_type_id}";
      $collection->add($route_name, $route);

    }

    return $collection;
  }

}
