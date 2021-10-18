<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag\Plugin\Field\FieldWidget\MetatagFirehose as Base;

/**
 * Extends metatags widget.
 */
class MetatagFirehose extends Base {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';
    $element['intro_text']['#access'] = FALSE;
    $element['basic']['#open'] = FALSE;
    $element['advanced']['#open'] = FALSE;
    if (!empty($element['#group'])) {
      $element['#group'] = FALSE;
    }

    return $element;
  }

}
