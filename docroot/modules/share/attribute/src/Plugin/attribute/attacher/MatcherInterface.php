<?php

namespace Drupal\attribute\Plugin\attribute\matcher;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\attribute\AttributeInterface;

/**
 * Provides an interface for all attribute plugins.
 */
interface MatcherInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function matchEntity(AttributeInterface $attribute_entity, EntityInterface $target_entity, EntityInterface $entity);

}
