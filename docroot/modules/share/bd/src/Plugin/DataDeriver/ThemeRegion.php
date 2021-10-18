<?php

namespace Drupal\bd\Plugin\DataDeriver;

use Drupal\bd\Plugin\EntityPluginBase;
use Drupal\bd\Php\Arr;

/**
 * Derives data from the default theme regions.
 *
 * @DataDeriver(
 *   plugin_type = "data_deriver",
 *   id = "theme_region",
 *   label = @Translation("Theme Regions"),
 *   help = @Translation("Dervive data from the default theme regions.")
 * )
 */
class ThemeRegion extends EntityPluginBase {

  /**
   * @param array $data
   * @param array $context
   *
   * @return array
   */
  public function process(array &$data = [], array &$context = []) {
    $derived = [];

    $theme = \Drupal::theme()->getActiveTheme();

    foreach ($theme->getRegions() as $key_inner => $region_id) {

      $derived[$region_id] = [
        'id' => $region_id,
      ];

      if (empty($this->configuration['mapping'])) {
        continue;
      }

      $replacements = [];
      $replacements['derivative_id'] = $region_id;
      $mapping_config = $this->configuration['mapping'];
      Arr::replace($mapping_config, array_keys($replacements), array_values($replacements));

      foreach ($mapping_config as $mapping_key => $mapping_value) {
        $derived[$region_id][$mapping_key] = $mapping_value;
      }
    }

    return $derived;
  }

}
