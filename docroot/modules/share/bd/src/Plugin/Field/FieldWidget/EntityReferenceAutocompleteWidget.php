<?php

namespace Drupal\bd\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Extends entity reference autocomplete widget.
 */
class EntityReferenceAutocompleteWidget extends Base {

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
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $entity = $items->getEntity();
    $field_definition = $items->getFieldDefinition();
    $field_storage_defintion = $field_definition->getFieldStorageDefinition();

    // Get deriver plugin id.
    if ($deriver_id = $field_storage_defintion->getSetting('deriver')) {

      /** @var \Drupal\bd\PluginManager $plugin_manager_deriver */
      $plugin_manager_deriver = \Drupal::service('plugin.manager.entity_reference_deriver');

      /** @var \Drupal\bd\Plugin\PluginInterface $plugin */
      $plugin = $plugin_manager_deriver->createInstance($deriver_id);
      $options = $plugin->getOption($items, $entity);

      $plugin_definition = $plugin->getPluginDefinition();

      $element['derivative'] = [
        '#type' => 'select',
        '#title' => $plugin_definition['label'],
        '#options' => $options,
        '#description' => !empty($plugin_definition['description']) ? $plugin_definition['description'] : NULL,
        '#default_value' => $items->get($delta)->derivative,
        '#weight' => 1000,
        '#normalize' => TRUE,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];
      }
    }

    return $values;
  }

}
