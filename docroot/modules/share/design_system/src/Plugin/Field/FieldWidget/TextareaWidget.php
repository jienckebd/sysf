<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extends textarea widget in core.
 */
class TextareaWidget extends Base {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hide_format' => FALSE,
      'default_format' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['hide_format'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide format'),
      '#default_value' => $this->getSetting('hide_format'),
    ];

    $entities_format = \Drupal::service('entity.helper')->getStorage('filter_format')->loadMultiple();

    $options_format = [];

    /**
     * @var string $key
     * @var \Drupal\filter\FilterFormatInterface $format
     */
    foreach ($entities_format as $key => $format) {
      $options_format[$format->id()] = $format->label();
    }

    $element['default_format'] = [
      '#type' => 'select',
      '#normalize' => TRUE,
      '#title' => $this->t('Default format'),
      '#description' => $this->t('Optionally override the default text format. Otherwise, it will be inherited from the field definition.'),
      '#default_value' => $this->getSetting('default_format'),
      '#options' => $options_format,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $entity = $items->getEntity();

    $element['#hide_format'] = $this->getSetting('hide_format');

    if ($entity->isNew() && $default_format = $this->getSetting('default_format')) {
      $element['#format'] = $default_format;
    }

    return $element;
  }

}
