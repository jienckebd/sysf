<?php

namespace Drupal\design_system\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface as Base;

/**
 * Extends core entity display repository.
 */
interface EntityDisplayRepositoryInterface extends Base {

  /**
   * @param $entity_type
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isLayoutBuilderEnabled($entity_type);

}
