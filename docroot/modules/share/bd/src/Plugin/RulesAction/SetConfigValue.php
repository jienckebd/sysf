<?php

namespace Drupal\bd\Plugin\RulesAction;

use Drupal\Component\Utility\NestedArray;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides the 'Set config value' action.
 *
 * @RulesAction(
 *   id = "set_config_value",
 *   label = @Translation("Set config value"),
 *   category = @Translation("Config"),
 *   context_definitions = {
 *     "config_name" = @ContextDefinition("string",
 *       label = @Translation("Config name"),
 *       description = @Translation("The config name."),
 *       default_value = NULL,
 *       required = TRUE
 *     ),
 *     "config_key" = @ContextDefinition("string",
 *       label = @Translation("Config key"),
 *       description = @Translation("The dot noted config key."),
 *       default_value = NULL,
 *       required = TRUE
 *     ),
 *     "config_value" = @ContextDefinition("string",
 *       label = @Translation("Config value"),
 *       description = @Translation("The config value to set."),
 *       default_value = NULL,
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class SetConfigValue extends RulesActionBase {

  /**
   * @param $config_name
   * @param $config_key
   * @param $config_value
   */
  protected function doExecute($config_name, $config_key, $config_value) {
    if (empty($config_value)) {
      return;
    }

    if ($config_name != $_ENV['SYS_RULES_TMP_CONFIG_NAME']) {
      return;
    }

    $config_overrides = [];
    $parents = explode('.', $config_key);
    array_unshift($parents, $config_name);
    NestedArray::setValue($config_overrides, $parents, $config_value);

    $_ENV['SYS_RULES_TMP_CONFIG_OVERRIDES'] = $config_overrides;

  }

}
