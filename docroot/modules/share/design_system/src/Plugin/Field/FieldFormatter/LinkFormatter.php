<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extends link formatter in core.
 */
class LinkFormatter extends Base {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title_only' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['title_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Title only'),
      '#default_value' => $this->getSetting('title_only'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    if ($title_only = $this->getSetting('title_only')) {
      $element = [];
      foreach ($items as $delta => $field_item) {
        $element[$delta] = [
          '#markup' => $field_item->title,
        ];
      }
      return $element;
    }

    $element = parent::viewElements($items, $langcode);
    return $element;
  }

}
