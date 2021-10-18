<?php

namespace Drupal\bd\Plugin\Action\Derivative;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Action\Plugin\Action\Derivative\EntityActionDeriverBase;

/**
 * Provides an action deriver that finds entity types of ContentEntityInterface.
 *
 * @see \Drupal\Core\Action\Plugin\Action\SaveAction
 */
class ContentEntityActionDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements(ContentEntityInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $definitions = [];
      foreach ($this->getApplicableEntityTypes() as $entity_type_id => $entity_type) {
        $definition = $base_plugin_definition;
        $definition['type'] = $entity_type_id;
        $definition['label'] = sprintf('%s %s', $base_plugin_definition['action_label'], $entity_type->getLabel());
        $definitions[$entity_type_id] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return $this->derivatives;
  }

}
