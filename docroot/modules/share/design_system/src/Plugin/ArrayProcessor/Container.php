<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides computed field values based on related entity values.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "container",
 *   label = @Translation("Containers"),
 *   description = @Translation("Renders recursive containers."),
 * )
 */
class Container extends Base {

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $context['entity'];

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    if (!$entity->hasField('container')) {
      return;
    }

    if (!$entity->container->isEmpty()) {
      $entity_container = $entity->container->entity;
      $this->processContainer($build, $entity_container);
    }

  }

  /**
   * @param array $build
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_container
   */
  protected function processContainer(array &$build, ContentEntityInterface $entity_container) {

    $config_container = [
      'wrapper_tag' => 'div',
      'attributes' => [
        'class' => [
          'container--wrapper',
        ],
      ],
    ];

    if ($width = $entity_container->field_width->value) {
      // $config_container['attributes']['style'] = "width: {$width};";
    }

    $build['#containers'][] = $config_container;

    if (!$entity_container->container->isEmpty()) {
      $entity_container_child = $entity_container->container->entity;
      $this->processContainer($build, $entity_container_child);
    }

  }

}
