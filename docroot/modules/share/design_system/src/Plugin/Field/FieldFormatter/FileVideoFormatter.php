<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter as Base;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extends html5 video formatter from file module.
 */
class FileVideoFormatter extends Base {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'video';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'muted' => FALSE,
      'width' => NULL,
      'height' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['width']['#required'] = FALSE;
    $form['width']['#type'] = 'textfield';
    unset($form['width']['#min']);
    unset($form['width']['#field_suffix']);
    unset($form['width']['#maxlength']);
    $form['height']['#required'] = FALSE;
    $form['height']['#type'] = 'textfield';
    unset($form['height']['#min']);
    unset($form['height']['#field_suffix']);
    unset($form['height']['#maxlength']);

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
   * {@inheritdoc}
   */
  protected function prepareAttributes(array $additional_attributes = []) {
    $attributes = parent::prepareAttributes($additional_attributes);
    return $attributes;
  }

}
