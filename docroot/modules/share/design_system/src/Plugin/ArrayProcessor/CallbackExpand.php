<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Render\Element;

/**
 * Expand array based on callbacks in a defined property.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "callback_expand",
 *   label = @Translation("Callback expand"),
 *   description = @Translation("Expand array based on callbacks in a defined property."),
 * )
 */
class CallbackExpand extends Base {

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {

    $callback_property = $this->configuration['callback_property'];
    $this->recurseExpandEntityBuild($build, $callback_property);

  }

  /**
   * Expand all entity builds at once.
   *
   * @param array $build
   * @param $callback_property
   */
  protected function recurseExpandEntityBuild(array &$build, $callback_property) {

    foreach (Element::children($build) as $child_key) {

      $child = &$build[$child_key];
      if (!is_array($child)) {
        continue;
      }

      if (!empty($child[$callback_property])) {
        $pre_renders = $child[$callback_property];
        unset($child[$callback_property]);
        foreach ($pre_renders as $callable) {
          $child = call_user_func_array($callable, [$child]);
        }
      }

      $this->recurseExpandEntityBuild($child, $callback_property);
    }

  }

}
