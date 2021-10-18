<?php

namespace Drupal\bd\Plugin\ValueProvider;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Provides computed field values based on related entity values.
 *
 * @ValueProvider(
 *   plugin_type = "value_provider",
 *   id = "data_deriver",
 *   label = @Translation("Data deriver"),
 *   description = @Translation("Provide derived values."),
 * )
 */
class DataDeriver extends Base {

  /**
   * @return array
   */
  public function getValue(FieldableEntityInterface $entity, FieldConfigInterface $field_config) {

    /** @var \Drupal\bd\PluginManager\EntityPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.data_deriver');
    $plugin_id = $this->configuration['data_deriver_plugin']['plugin_id'];
    $plugin_config = $this->configuration['data_deriver_plugin']['plugin_configuration'];

    $plugin_instance = $plugin_manager->createInstance($plugin_id, $plugin_config);
    $data = [];
    $context = [];
    $raw_values = $plugin_instance->process($data, $context);

    $field_config_settings = $field_config->getSettings();
    $target_entity_type_id = $field_config->getFieldStorageDefinition()->getSetting('target_type');
    $target_entity_storage = $this->entityHelper->getStorage($target_entity_type_id);

    $target_bundle_id = reset($field_config_settings['handler_settings']['target_bundles']);

    $values = [];
    foreach ($raw_values as $key => $value) {
      $entity_values = [
        'bundle' => $target_bundle_id,
        'label' => $value['field_config']['label'],
      ];
      $values[] = [
        'entity' => $target_entity_storage->create($entity_values),
      ];
    }

    if (!empty($plugin_config['propagate_field_name'])) {

      // Create default entity based on entity reference selection handler.
      $default_collection = \Drupal::service('entity.helper')->getStorage('dom')->create([
        'bundle' => 'collection',
      ]);

      $default_collection->set($plugin_config['propagate_field_name'], $values);

      $values = [];
      $values[] = [
        'entity' => $default_collection,
        'target_id' => NULL,
      ];

    }

    return $values;
  }

}
