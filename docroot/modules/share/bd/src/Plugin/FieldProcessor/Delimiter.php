<?php

namespace Drupal\bd\Plugin\FieldProcessor;

use Drupal\bd\Plugin\EntityPluginBase;

/**
 * Provides computed field values based on related entity values.
 *
 * @FieldProcessor(
 *   plugin_type = "field_processor",
 *   id = "delimiter",
 *   label = @Translation("Delimiter"),
 *   description = @Translation("Determine how multiple field values are delimited."),
 * )
 */
class Delimiter extends EntityPluginBase {

  /**
   * @param array $build
   */
  public function processBuild(array &$build) {

  }

}
