<?php

namespace Drupal\design_system\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'List count comparison' condition.
 *
 * @Condition(
 *   id = "request",
 *   label = @Translation("Request"),
 *   category = @Translation("System"),
 *   context_definitions = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path pattern"),
 *       description = @Translation("The current path pattern."),
 *       required = false,
 *     ),
 *   }
 * )
 */
class Request extends RulesConditionBase {

  /**
   * @param string $path
   * @return bool
   */
  protected function doEvaluate(string $path) {

    if (!empty($path)) {
      $path_info = \Drupal::request()->getPathInfo();
      if (fnmatch($path, $path_info)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
