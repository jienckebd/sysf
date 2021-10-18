<?php

namespace Drupal\design_system\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\EntityRow as Base;

/**
 * Extends views module row.
 */
class EntityRow extends Base {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $build = $this->getEntityTranslationRenderer()->render($row);
    return $build;
  }

}
