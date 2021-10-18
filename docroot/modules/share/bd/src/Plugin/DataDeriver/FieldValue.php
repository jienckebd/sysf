<?php

namespace Drupal\bd\Plugin\DataDeriver;

use Drupal\bd\Plugin\EntityPluginBase;

/**
 * Derives data from an entity collection.
 *
 * @DataDeriver(
 *   plugin_type = "data_deriver",
 *   id = "field_value",
 *   label = @Translation("Field value"),
 *   help = @Translation("Dervive data from entity field values of a specified field."),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", required = TRUE, label = @Translation("Entity"))
 *   }
 * )
 */
class FieldValue extends EntityPluginBase {

  /**
   * @param array $data
   * @param array $context
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(array &$data = [], array &$context = []) {

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity_parent = $context['entity'];

    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $context['field_config'];

    $derived = [];
    if (!$entity = $this->getContext('entity')->getContextValue()) {
      return $derived;
    }

    $value_selector = !empty($this->configuration['value_selector']) ? $this->configuration['value_selector'] : '';

    if (empty($value_selector)) {
      return $derived;
    }

    if (stripos($value_selector, '.') === FALSE) {
      $field_items = $entity->get($value_selector);
    }
    else {
      $selector_components = explode('.', $value_selector);
      $selection = NULL;
      $current_entity = $entity;
      foreach ($selector_components as $component_id) {

        if ($component_id == 'entity') {
          if (!$current_entity = $selection->entity) {
            break;
          }
        }
        else {
          if (!$current_entity->hasField($component_id)) {
            \Drupal::logger('entity')->warning("Missing component ID {$component_id}.");
            continue;
          }
          $selection = $current_entity->get($component_id);
        }
      }

      $field_items = $selection;
    }

    if (empty($field_items)) {
      return $derived;
    }

    if ($field_items->isEmpty()) {
      return $derived;
    }

    $entity_storage = $this->entityHelper->getStorage('dom');
    foreach ($field_items as $delta => $section_field_item) {

      $layout_plugin = $section_field_item->getContainedPluginInstance();
      $layout_plugin_definition = $layout_plugin->getPluginDefinition();

      foreach ($layout_plugin_definition->getRegions() as $region_id => $region_config) {

        $derivative = [
          'machine_name' => $region_id,
          'label' => $region_config['label'],
        ];

        $variants = [];
        $variants[] = [
          'entity' => $entity_storage->create([
            'bundle' => 'region',
            'label' => 'todo',
          ]),
          'target_id' => NULL,
        ];

        if (!empty($variants)) {
          // $derivative['field_variant'] = $variants;
        }

        if (!empty($layout_settings['region'][$region_id]['is_default'])) {
          $derivative['field_default'] = TRUE;
        }

        $derived[] = $derivative;

      }

    }

    return $derived;
  }

}
