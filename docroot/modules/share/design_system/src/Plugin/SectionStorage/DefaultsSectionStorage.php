<?php

namespace Drupal\design_system\Plugin\SectionStorage;

use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage as Base;

/**
 * Extends layout_builder.
 */
class DefaultsSectionStorage extends Base {

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return parent::getRedirectUrl();
  }

}
