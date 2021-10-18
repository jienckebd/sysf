<?php

namespace Drupal\bd\Plugin\DataDeriver;

use Drupal\bd\Plugin\EntityPluginBase;

/**
 * Derives data from an entity collection.
 *
 * @DataDeriver(
 *   plugin_type = "data_deriver",
 *   id = "plugin_collection",
 *   label = @Translation("Plugin Collection"),
 *   help = @Translation("Dervive data from a plugin collection.")
 * )
 */
class PluginCollection extends EntityPluginBase {

  /**
   * @param array $data
   * @param array $context
   *
   * @return array
   */
  public function process(array &$data = [], array &$context = []) {

    $plugin_manager_service_id = $this->configuration['service_id'];

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service($plugin_manager_service_id);

    if (!$plugin_definitions = $plugin_manager->getDefinitions()) {
      return FALSE;
    }

    foreach ($plugin_definitions as $plugin_id => $plugin) {

      $plugin_definition = $plugin->toArray();

      $derivative = [];
      $derivative_id = $plugin_definition['id'];

      if (!empty($this->configuration['mapping'])) {
        foreach ($this->configuration['mapping'] as $target_field => $source_property) {
          if (isset($plugin_definition[$source_property])) {
            $derivative[$target_field] = $plugin_definition[$source_property];
          }
        }
      }

      $derived[$derivative_id] = $derivative;
    }

    $derived = [];

    return $derived;
  }

}
