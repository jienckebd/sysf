<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter as Base;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extends string formatter in core.
 */
class StringFormatter extends Base {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['filter_format'] = NULL;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $filter_formats = $this->entityHelper->getStorage('filter_format')->loadMultiple();
    $options_filter_format = [];
    foreach ($filter_formats as $entity_id => $entity) {
      $options_filter_format[$entity_id] = $entity->label();
    }

    $form['filter_format'] = [
      '#type' => 'select',
      '#normalize' => TRUE,
      '#title' => $this->t('Filter format'),
      '#default_value' => $this->getSetting('filter_format'),
      '#options' => $options_filter_format,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    if ($filter_format = $this->getSetting('filter_format')) {
      return [
        '#type' => 'processed_text',
        '#format' => $filter_format,
        '#text' => $item->value,
      ];
    }

    return parent::viewValue($item);
  }

}
