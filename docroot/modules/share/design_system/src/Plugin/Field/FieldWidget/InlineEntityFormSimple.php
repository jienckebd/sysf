<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormSimple as Base;

/**
 * Extends IEF simple widget.
 */
class InlineEntityFormSimple extends Base {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['default_bundle'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['default_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Default bundle'),
      '#options' => $this->getTargetBundles(),
      '#default_value' => $this->getSetting('default_bundle'),
      '#normalize' => TRUE,
    ];

    if (empty($element['default_bundle']['#options'])) {
      $element['default_bundle']['#options_provider'] = [
        'plugin_id' => 'bundle',
        'plugin_config' => [
          'entity_type' => $this->getFieldSetting('target_type'),
        ],
      ];
    }

    return $element;
  }

  /**
   * Gets the bundle for the inline entity.
   *
   * @return string|null
   *   The bundle, or NULL if not known.
   */
  protected function getBundle() {
    if ($default_bundle = $this->getSetting('default_bundle')) {
      return $default_bundle;
    }
    return parent::getBundle();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
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
