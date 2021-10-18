<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;

/**
 *
 */
trait DynamicEntityReferenceTrait {

  /**
   * @return array
   */
  public static function getDynamicEntityReferenceDefaultSettings() {
    $labels = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
    $options = array_keys($labels[(string) t('Content', [], ['context' => 'Entity type group'])]);
    return array_fill_keys($options, ['view_mode' => 'default', 'link' => FALSE]);
  }

  /**
   * @return mixed
   */
  protected function getDynamicEntityReferenceViewModeElement() {
    $labels = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
    $options = $labels[(string) t('Content', [], ['context' => 'Entity type group'])];
    $entity_type_ids = DynamicEntityReferenceItem::getTargetTypes($this->getFieldSettings());
    $elements['view_mode'] = [];

    foreach ($entity_type_ids as $entity_type_id) {
      $elements[$entity_type_id] = [
        '#type' => 'container',
      ];
      $elements[$entity_type_id]['view_mode'] = [
        '#type' => 'select',
        '#options' => $this->entityDisplayRepository->getViewModeOptions($entity_type_id),
        '#title' => t('View mode for %entity', ['%entity' => $options[$entity_type_id]]),
        '#default_value' => $this->getSetting($entity_type_id)['view_mode'],
        '#required' => TRUE,
      ];
    }

    return $elements;
  }

}
