<?php

namespace Drupal\design_system\Entity;

use Drupal\Core\Entity\EntityDisplayRepository as Base;

/**
 * Extends core entity display repository.
 */
class EntityDisplayRepository extends Base implements EntityDisplayRepositoryInterface {

  /**
   * {@inheritDoc}
   */
  public function isLayoutBuilderEnabled($entity_type, $bundle_id = NULL, $mode_id = NULL) {
    if (is_string($entity_type)) {
      $entity_type = $this->entityHelper->getDefinition($entity_type);
    }

    $form_config = $entity_type->get('display') ?: [];

    if (empty($form_config['form']['entity_view_display']['enabled'])) {
      return FALSE;
    }

    return TRUE;
  }

}
