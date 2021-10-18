<?php

namespace Drupal\autoref\Plugin\autoref\matcher;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\autoref\AutorefInterface;

/**
 * Provides an interface for all autoref plugins.
 */
interface MatcherInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function matchEntity(AutorefInterface $autoref_entity, EntityInterface $target_entity, EntityInterface $entity);

}
