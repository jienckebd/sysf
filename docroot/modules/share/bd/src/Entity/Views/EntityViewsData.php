<?php

namespace Drupal\bd\Entity\Views;

use Drupal\views\EntityViewsData as Base;

/**
 * Provides the views data for generic entity types.
 */
class EntityViewsData extends Base {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}
