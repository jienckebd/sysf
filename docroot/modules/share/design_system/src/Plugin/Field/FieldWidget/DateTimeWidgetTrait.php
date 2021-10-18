<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides logic to datetime_default and daterange_default widgets.
 */
trait DateTimeWidgetTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hide_time' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['hide_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide time element and default to midnight'),
      '#default_value' => $this->getSetting('hide_time'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value']['#settings'] = $this->getSettings();
    $element['value']['#pre_render'][] = [$this, 'processDateTimeWidget'];

    if (!empty($element['end_value'])) {
      $element['end_value']['#settings'] = $this->getSettings();
      $element['end_value']['#pre_render'][] = [$this, 'processDateTimeWidget'];
    }

    return $element;
  }

  /**
   * @param array $element
   *
   * @return array
   */
  public function processDateTimeWidget(array $element) {

    if (!empty($element['#settings']['hide_time'])) {
      $element['time']['#wrapper_attributes']['class'][] = 'visually-hidden';
      $element['time']['#value'] = '00:00:00';
      $element['time']['#default_value'] = '00:00:00';
    }

    return $element;
  }

}
