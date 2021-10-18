<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\Core\Render\Element;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex as Base;

/**
 * Extends IEF complex widget.
 */
class InlineEntityFormComplex extends Base {

  /**
   * Extends IEF to support dynamic_entity_reference.
   */
  protected function createInlineFormHandler() {
    if ($this->fieldDefinition->getType() != 'dynamic_entity_reference') {
      return parent::createInlineFormHandler();
    }
    $first_entity_type_id = 'node';
    $this->inlineFormHandler = $this->entityHelper->getHandler($first_entity_type_id, 'inline_form');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults['allow_existing'] = TRUE;
    $defaults['allow_duplicate'] = TRUE;
    return $defaults;
  }

  /**
   * {@inheritDoc}
   */
  protected function canBuildForm(FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Allow collapsible config to use details but otherwise use container.
    if ($element['#type'] == 'fieldset') {
      $element['#type'] = 'container';
    }

    // Make add another button small.
    if (!empty($element['actions'])) {
      foreach ($element['actions'] as $key => &$child) {
        if (is_array($child) && isset($child['#type']) && in_array($child['#type'], ['button', 'submit'])) {
          $child['#button_size'] = 'sm';
        }
      }
    }

    foreach (Element::children($element['entities']) as $delta) {

      $child = &$element['entities'][$delta];
      if (!is_array($child) || empty($child['actions'])) {
        continue;
      }

      foreach (Element::children($child['actions']) as $inner_child_key) {

        $inner_child = &$child['actions'][$inner_child_key];
        if (!is_array($inner_child) || empty($inner_child['#type']) || !in_array($inner_child['#type'], ['submit', 'button'])) {
          continue;
        }

        $inner_child['#button_size'] = 'sm';

      }

    }

    $this->setSetting('display_label', TRUE);
    if ($this->getSetting('display_label')) {
      $element['label'] = [
        '#type' => 'label',
        '#title' => $this->fieldDefinition->getLabel(),
        '#weight' => -1000,
      ];
    }

    if (!empty($element['#description'])) {
      $element['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $element['#description'],
        '#attributes' => [
          'class' => [
            'description',
          ],
        ],
        '#weight' => 1000,
      ];
    }

    return $element;
  }

}
