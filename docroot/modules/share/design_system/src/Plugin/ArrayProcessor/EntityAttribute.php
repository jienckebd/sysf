<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Entity\RevisionableInterface;

/**
 * Attaches standard entity attributes.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "entity_attribute",
 *   label = @Translation("Entity attributes"),
 *   description = @Translation("Attaches standard entity attributes."),
 * )
 */
class EntityAttribute extends Base {

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $context['entity'];
    $view_mode_id = $context['view_mode_id'];
    $entity_type_id = $entity->getEntityTypeId();

    $entity_id = $entity->id();
    $langcode = $entity->language()->getId();

    $build['#attributes']['class'][] = 'entity';

    $build['#attributes']['data-entity-type'] = $entity_type_id;
    $build['#attributes']['data-entity-id'] = $entity_id;

    if ($entity instanceof RevisionableInterface) {
      $build['#attributes']['data-revision-id'] = $entity->getRevisionId();
    }

    $build['#attributes']['data-uuid'] = $entity->uuid();

    if ($bundle = $entity->bundle()) {
      $build['#attributes']['data-bundle'] = $bundle;
    }

    $build['#attributes']['data-view-mode'] = $view_mode_id;
    $build['#attributes']['data-langcode'] = $langcode;

  }

}
