<?php

namespace Drupal\design_system\Plugin\EntityReferenceDeriver;

use Drupal\bd\Plugin\EntityReferenceDeriver\Base;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides derivatives around display modes.
 *
 * @EntityReferenceDeriver(
 *   plugin_type = "entity_reference_deriver",
 *   id = "theme_entity_breakpoint",
 *   label = @Translation("Theme entity: Breakpoint"),
 *   description = @Translation("Derives around theme entity breakpoints."),
 * )
 */
class ThemeEntityBreakpoint extends Base {

  /**
   * {@inheritDoc}
   */
  public function getOption(FieldItemListInterface $field_items, EntityInterface $entity) {
    $entity_type_id = $field_items->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
    $options = $this->entityDisplayRepository->getViewModeOptions($entity_type_id);
    return $options;
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(array &$build, array $formatter_settings, FieldItemListInterface $items, $langcode) {

    $target_entity_type_id = $items->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
    $entity_storage_entity_display_mode = $this->entityHelper->getStorage('entity_view_mode');

    foreach ($items as $delta => $field_item) {

      if (empty($build[$delta])) {
        continue;
      }

      if (empty($formatter_settings['show_derivative_label'])) {
        continue;
      }

      $derivative_id = $field_item->derivative;
      if ($derivative_id != 'default') {
        $entity_id_display_mode = "{$target_entity_type_id}.{$field_item->derivative}";
        $entity_display_mode = $entity_storage_entity_display_mode->load($entity_id_display_mode);
        $derivative_label = $entity_display_mode->label();
      }
      else {
        $derivative_label = $this->t('Default');
      }

      $build[$delta]['#view_mode'] = $derivative_id;
      $build[$delta]['#derivative_label_class'] = !empty($formatter_settings['derivative_label_class']) ? $formatter_settings['derivative_label_class'] : NULL;
      $build[$delta]['#derivative_label'] = $derivative_label;
      $build[$delta]['#pre_render'][] = [static::class, 'preRenderBuild'];

    }

  }

  /**
   * Process deriver config.
   *
   * @param array $build
   *   The entity display build.
   *
   * @return array
   */
  public static function preRenderBuild(array $build) {

    if (!empty($build['_field_layout']['#layout'])) {
      $base = &$build['_field_layout'];
    }
    else {
      $base = &$build;
    }

    $base['derivative_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $build['#derivative_label'],
      '#attributes' => [
        'class' => [
          'entity-reference--derivative--label',
        ],
      ],
      '#weight' => -1000,
    ];

    if (!empty($build['#derivative_label_class'])) {
      foreach (explode(' ', $build['#derivative_label_class']) as $key => $class) {
        $base['derivative_label']['#attributes']['class'][] = $class;
      }
    }

    return $build;
  }

}
