<?php

namespace Drupal\bd\Plugin\FieldProcessor;

use Drupal\bd\Plugin\EntityPluginBase;

/**
 * Provides computed field values based on related entity values.
 *
 * @FieldProcessor(
 *   plugin_type = "field_processor",
 *   id = "max_show",
 *   label = @Translation("Max show"),
 *   description = @Translation("Specify a maximum number of items to show."),
 * )
 */
class MaxShow extends EntityPluginBase {

  /**
   * @param array $build
   */
  public function processBuild(array &$build) {

  }

}
