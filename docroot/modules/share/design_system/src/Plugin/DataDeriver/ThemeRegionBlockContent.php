<?php

namespace Drupal\design_system\Plugin\DataDeriver;

use Drupal\bd\Plugin\EntityPluginBase;

/**
 * Derives data from theme region block content.
 *
 * @DataDeriver(
 *   plugin_type = "data_deriver",
 *   id = "theme_region_block_content",
 *   label = @Translation("Theme region block content"),
 *   help = @Translation("Dervive data from blocks of a region of a theme.")
 * )
 */
class ThemeRegionBlockContent extends EntityPluginBase {

  /**
   * {@inheritDoc}
   */
  public function process(array &$data = [], array &$context = []) {

    $derived = [];

    $theme_handler = \Drupal::service("theme_handler");

    $theme_entity_id = $this->configuration['theme_id'];
    $region_id = $this->configuration['region_id'];
    $theme_name = $theme_handler->getThemeNameFromEntityId($theme_entity_id);

    $entity_storage_block_content = $this->entityHelper->getStorage('block_content');

    $entity_blocks = $this->entityHelper
      ->getStorage('block')
      ->loadByProperties([
        'theme' => $theme_name,
        'region' => $region_id,
      ]);

    foreach ($entity_blocks as $entity_id_block => $entity_block) {

      $block_plugin_id = $entity_block->get('plugin');
      if (!fnmatch("block_content:*", $block_plugin_id)) {
        continue;
      }

      $block_content_uuid = explode('block_content:', $block_plugin_id)[1];
      $entity_block_content = $entity_storage_block_content->loadByProperties(['uuid' => $block_content_uuid]);
      $entity_block_content = reset($entity_block_content);

      $derived[] = [
        'target_id' => $entity_block_content->id(),
        'entity' => $entity_block_content,
      ];

    }

    return $derived;
  }

}
