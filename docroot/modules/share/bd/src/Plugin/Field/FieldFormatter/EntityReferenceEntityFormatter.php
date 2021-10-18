<?php

namespace Drupal\bd\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Extends entity formatter in core.
 */
class EntityReferenceEntityFormatter extends Base implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_derivative_label' => FALSE,
      'derivative_label_class' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_derivative_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show derivative label'),
      '#default_value' => $this->getSetting('show_derivative_label'),
    ];

    $elements['derivative_label_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Derivative label classes'),
      '#description' => $this->t('Apply space separated classes to the derivative label.'),
      '#default_value' => $this->getSetting('derivative_label_class'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $field_definition = $items->getFieldDefinition();
    $field_storage_defintion = $field_definition->getFieldStorageDefinition();

    if ($deriver_id = $field_storage_defintion->getSetting('deriver')) {
      /** @var \Drupal\bd\PluginManager $plugin_manager_deriver */
      $plugin_manager_deriver = \Drupal::service('plugin.manager.entity_reference_deriver');

      /** @var \Drupal\bd\Plugin\PluginInterface $plugin */
      $plugin = $plugin_manager_deriver->createInstance($deriver_id);
      $plugin->viewElements($elements, $this->getSettings(), $items, $langcode);
    }

    return $elements;
  }

}
