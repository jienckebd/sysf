<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'range_slider' widget.
 *
 * @FieldWidget(
 *   id = "range_slider",
 *   label = @Translation("Range slider"),
 *   field_types = {
 *     "float",
 *     "integer",
 *     "decimal"
 *   }
 * )
 */
class RangeSlider extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_uuid = $entity->uuid();
    $field_name = $items->getName();

    $slider_id = "{$entity_type_id}--{$entity_uuid}--{$field_name}--{$delta}";
    $slider_value_id = "{$slider_id}--value";

    $min = $this->fieldDefinition->getSetting('min');
    $max = $this->fieldDefinition->getSetting('max');

    $field_value = $items->get($delta)->getValue();
    $raw_value = $field_value['value'];

    $element = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'range-slider--wrapper',
        ],
      ],
    ];

    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#title_display' => 'invisible',
      '#default_value' => $raw_value,
      '#attributes' => [
        'id' => $slider_value_id,
      ],
      '#wrapper_attributes' => [
        'class' => [
          'visually-hidden',
        ],
      ],
    ];

    $element['slider'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => $slider_id,
        'data-slider-value-id' => $slider_value_id,
        'class' => [
          'range-slider',
        ],
      ],
    ];

    $element['#attached']['library'][] = 'alpha/slider';
    $element['#attached']['drupalSettings']['design_system']['slider'][$slider_id] = [
      'min' => $min,
      'max' => $max,
      'value' => $raw_value,
      'step' => 1,
      'labels' => TRUE,
    ];

    return $element;
  }

}
