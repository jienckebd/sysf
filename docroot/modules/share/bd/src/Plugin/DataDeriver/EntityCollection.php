<?php

namespace Drupal\bd\Plugin\DataDeriver;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\bd\Php\Str;
use Drupal\bd\Plugin\EntityPluginBase;

/**
 * Derives data from an entity collection.
 *
 * @DataDeriver(
 *   plugin_type = "data_deriver",
 *   id = "entity_collection",
 *   label = @Translation("Entity Collection"),
 *   help = @Translation("Dervive data from an entity collection.")
 * )
 */
class EntityCollection extends EntityPluginBase {

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

    $deriver_definition = $this->configuration;

    $derived = [];
    $entity_helper = \Drupal::service('entity.helper');
    $derived_entity_type_id = $deriver_definition['entity_type'];
    $derived_bundle_id = isset($deriver_definition['bundle']) ? $deriver_definition['bundle'] : NULL;

    $entity_type = $entity_helper->getDefinition($derived_entity_type_id);

    if ($bundle_of = $entity_type->getBundleOf()) {
      $bundled_entity_type = $entity_helper->getDefinition($bundle_of);
      $bundle_of_bundle_key = $bundled_entity_type->getKey('bundle');
    }

    $bundle_key = $entity_type->getKey('bundle');

    $entity_storage_deriver = $entity_helper->getStorage($derived_entity_type_id);

    if (!empty($derived_bundle_id) && $entity_type->getBundleEntityType()) {
      $entity_derivatives = $entity_storage_deriver->loadByProperties([
        $bundle_key => $derived_bundle_id,
      ]);
    }
    else {
      $entity_derivatives = $entity_storage_deriver->loadMultiple();
    }

    if (empty($entity_derivatives)) {
      return $data;
    }

    foreach ($entity_derivatives as $entity_derivative) {
      $derived_field_definition = [];

      if (!empty($bundle_of_bundle_key)) {
        $derived_field_definition[$bundle_of_bundle_key] = $entity_derivative->id();
      }

      $derived_field_definition['field_config']['label'] = $entity_derivative->label();

      if ($entity_type->getDataTable()) {
        if ($entity_derivative->hasField('tag')) {
          $field_tag = $entity_derivative->get('tag');
          if (!$field_tag->isEmpty()) {
            $derived_field_definition['field_config']['third_party_settings']['bd']['group'] = $field_tag->target_id;
          }
        }
      }

      $id = $entity_derivative->label();

      $derived_field_name = Str::sanitizeMachineName($id);

      if (!empty($deriver_definition['mapping'])) {
        foreach ($deriver_definition['mapping'] as $selector_data => $selector_config) {

          if (!empty($selector_config['field_name'])) {

            $field_name = $selector_config['field_name'];

            if (!$entity_derivative->hasField($field_name)) {
              continue;
            }

            if ($entity_derivative->get($field_name)->isEmpty()) {
              continue;
            }

            $value = $entity_derivative->get($field_name)->getValue();
          }
          elseif (!empty($selector_config['static'])) {
            $value = $selector_config['static'];
          }
          else {
            continue;
          }

          if (!empty($selector_config['property'])) {
            $property = $selector_config['property'];

            if (!empty($selector_config['flatten'])) {
              $tmp = $value;
              $value = [];
              foreach ($tmp as $delta => $values) {
                $value[$values[$property]] = $values[$property];
              }
            }

            if (is_array($value) && !empty($value[0][$property])) {
              $value = $value[0][$property];
            }
          }

          $data_parents = explode("||", $selector_data);

          NestedArray::setValue($derived_field_definition, $data_parents, $value, TRUE);

        }
      }

      if ($entity_derivative->getEntityTypeId() == 'dom') {
        $derived_field_definition['dom'] = [
          'target_id' => $entity_derivative->id(),
        ];
      }

      $derived[$derived_field_name] = $derived_field_definition;
    }

    // @todo needs to run before CSV to allow overwrite from plugin.
    if (!empty($this->configuration['base_definition'])) {
      foreach ($this->configuration['base_definition'] as $key => $child) {
        foreach ($derived as $derived_id => &$derived_value) {
          $derived_value[$key] = $child;
        }
      }
    }

    return $derived;
  }

}
