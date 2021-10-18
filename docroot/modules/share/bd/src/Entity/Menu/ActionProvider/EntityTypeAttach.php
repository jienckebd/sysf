<?php

namespace Drupal\bd\Entity\Menu\ActionProvider;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\entity\Menu\EntityLocalActionProviderInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a action link to the add page or add form on the collection.
 */
class EntityTypeAttach implements EntityLocalActionProviderInterface, EntityHandlerInterface {

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
  public function buildLocalActions(EntityTypeInterface $entity_type) {
    $actions = [];

    $entity_type_id = $entity_type->id();

    foreach ($this->entityHelper->getDefinitionsByTag('display') as $other_entity_type_id => $other_entity_type) {

      if (!$admin_permission = $entity_type->get('admin_permission')) {
        continue;
      }

      $route_name = "entity.{$other_entity_type_id}.fields.add.{$entity_type_id}";

      $actions[$route_name] = [
        'title' => $this->t('Add @entity', [
          '@entity' => $entity_type->getSingularLabel(),
        ]),
        'route_name' => $route_name,
        'options' => [
          'query' => [
            'destination' => '<current>',
          ],
        ],
        'appears_on' => ["entity.{$other_entity_type_id}.field_ui_fields"],
        'class' => '\Drupal\bd\Plugin\Menu\LocalAction\RedirectToCurrent',
      ];

    }

    return $actions;
  }

}
