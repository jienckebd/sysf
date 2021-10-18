<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extends address widget.
 */
class AddressDefaultWidget extends Base {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $build = parent::formElement($items, $delta, $element, $form, $form_state);
    $build['#type'] = 'container';
    return $build;
  }

}
