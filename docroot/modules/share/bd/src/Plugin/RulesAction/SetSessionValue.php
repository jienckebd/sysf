<?php

namespace Drupal\bd\Plugin\RulesAction;

use Drupal\Component\Utility\NestedArray;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides the 'Set session value' action.
 *
 * @RulesAction(
 *   id = "set_session_value",
 *   label = @Translation("Set session value"),
 *   category = @Translation("System"),
 *   context_definitions = {
 *     "key" = @ContextDefinition("string",
 *       label = @Translation("Key"),
 *       description = @Translation("The dot noted key."),
 *       default_value = NULL,
 *       required = TRUE
 *     ),
 *     "value" = @ContextDefinition("string",
 *       label = @Translation("Value"),
 *       description = @Translation("The value to set."),
 *       default_value = NULL,
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class SetSessionValue extends RulesActionBase {

  /**
   * @param $key
   * @param $value
   */
  protected function doExecute($key, $value) {
    $key_parents = explode('.', $key);
    NestedArray::setValue($_SESSION, $key_parents, $value);
  }

}
