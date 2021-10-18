<?php

namespace Drupal\design_system\Context;

use Drupal\bd\Context\Base;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\Core\Plugin\Context\EntityContext;

/**
 * Sets the current group as a context on group routes.
 */
class EntityDisplay extends Base {
  use LayoutBuilderContextTrait;

  /**
   * The entity display context chain.
   *
   * @var array
   */
  protected $entityDisplayChain;

  /**
   * @param $display_context_id
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $mode_id
   */
  public function addContext($display_context_id, EntityInterface $entity, $mode_id) {

    if (empty($this->entityDisplayChain[$display_context_id])) {
      $this->entityDisplayChain[$display_context_id] = [];
    }

    $value = [
      'entity' => EntityContext::fromEntity($entity),
      'mode' => new Context(new ContextDefinition('string'), $mode_id),
    ];

    array_unshift($this->entityDisplayChain[$display_context_id], $value);

  }

  /**
   * @param $display_context_id
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $mode_id
   */
  public function removeContext($display_context_id, EntityInterface $entity, $mode_id) {

    if (empty($this->entityDisplayChain[$display_context_id])) {
      $this->logger->warning("Missing display context @display_context_id", [
        '@display_context_id' => $display_context_id,
      ]);
      return;
    }

    $queue = $this->entityDisplayChain[$display_context_id];

    while (!empty($queue)) {
      $value = array_shift($queue);

      /** @var \Drupal\Core\Entity\EntityInterface $entity_in_queue */
      $entity_in_queue = $value['entity']->getContextValue();

      if ($entity_in_queue->uuid() == $entity->uuid()) {
        $this->entityDisplayChain[$display_context_id] = $queue;
        return;
      }

    }

    $this->logger->warning("Entity @entity_type @entity_id not found in display context @display_context_id queue.", [
      '@entity_type' => $entity->getEntityTypeId(),
      '@entity_id' => $entity->id(),
      '@display_context_id' => $display_context_id,
    ]);

  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    return [];
    $contexts = $this->getEntityContexts($unqualified_context_ids);

    foreach ($unqualified_context_ids as $unqualified_context_id) {

      if (stripos($unqualified_context_id, 'display.form.entity:parent') !== FALSE) {
        $parents_count = substr_count($unqualified_context_id, ':parent');
        $parents_key = $parents_count - 1;
        if (!empty($this->entityDisplayChain['view'][$parents_key])) {
          $contexts[$unqualified_context_id] = $this->entityDisplayChain['view'][$parents_key]['entity'];
        }
      }
      elseif (stripos($unqualified_context_id, 'display.view.entity:parent') !== FALSE) {
        $parents_count = substr_count($unqualified_context_id, ':parent');
        $parents_key = $parents_count - 1;
        if (!empty($this->entityDisplayChain['view'][$parents_key])) {
          $contexts[$unqualified_context_id] = $this->entityDisplayChain['view'][$parents_key]['entity'];
        }
      }
      elseif (stripos($unqualified_context_id, 'display.form.mode:parent') !== FALSE) {
        $parents_count = substr_count($unqualified_context_id, ':parent');
        $parents_key = $parents_count - 1;
        if (!empty($this->entityDisplayChain['view'][$parents_key])) {
          $contexts[$unqualified_context_id] = $this->entityDisplayChain['view'][$parents_key]['entity'];
        }
      }
      elseif (stripos($unqualified_context_id, 'display.view.mode:parent') !== FALSE) {
        $parents_count = substr_count($unqualified_context_id, ':parent');
        $parents_key = $parents_count - 1;
        if (!empty($this->entityDisplayChain['view'][$parents_key])) {
          $contexts[$unqualified_context_id] = $this->entityDisplayChain['view'][$parents_key]['mode'];
        }
      }

    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return [];

    // These routes don't have layout builder context available.
    $fill_routes = [
      'layout_builder.choose_block',
      'layout_builder.add_block',
    ];

    $route_name = $this->routeMatch->getRouteName();

    if (!in_array($route_name, $fill_routes)) {
      // Return [];.
    }

    $contexts = $this->getEntityContexts();

    $display_contexts = [
      'view',
      'form',
    ];

    foreach ($display_contexts as $display_context_id) {
      if (!empty($this->entityDisplayChain[$display_context_id])) {
        $context_id_entity = "display.{$display_context_id}.entity";
        $context_id_mode = "display.{$display_context_id}.mode";
        foreach (array_reverse($this->entityDisplayChain[$display_context_id]) as $key => $context_data) {
          $contexts[$context_id_entity] = $context_data['entity'];
          $contexts[$context_id_mode] = $context_data['mode'];
          $context_id_entity = "{$context_id_entity}:parent";
          $context_id_mode = "{$context_id_entity}:parent";
        }
      }
    }

    return $contexts;
  }

  /**
   * @param array $unqualified_context_ids
   *
   * @return array
   */
  protected function getEntityContexts(array $unqualified_context_ids = []) {
    $contexts = [];

    if ($section_storage = $this->routeMatch->getParameter('section_storage')) {
      $contexts += $section_storage->getContextsDuringPreview();
    }

    if (empty($unqualified_context_ids) || in_array('entity_from_route', $unqualified_context_ids)) {
      if ($entity_from_route = \Drupal::service('design.system')
        ->getEntityFromRoute()) {
        $contexts['entity_from_route'] = EntityContext::fromEntity($entity_from_route, 'Entity from route');
      }
    }

    return $contexts;
  }

}
