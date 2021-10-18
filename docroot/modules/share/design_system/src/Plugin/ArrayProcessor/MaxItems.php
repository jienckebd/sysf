<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

/**
 * Attaches standard entity attributes.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "max_items",
 *   label = @Translation("Max items"),
 *   description = @Translation("Show a maximum number of items."),
 * )
 */
class MaxItems extends Base {

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {
  }

}
