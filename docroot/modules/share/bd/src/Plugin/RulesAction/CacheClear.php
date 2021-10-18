<?php

namespace Drupal\bd\Plugin\RulesAction;

/**
 * Class BuildCss.
 *
 * @RulesAction(
 *   id = "cache_clear",
 *   label = @Translation("Cache: Clear"),
 *   category = @Translation("Cache"),
 *   context_definitions = {
 *     "all" = @ContextDefinition("boolean",
 *       label = @Translation("All caches"),
 *       description = @Translation("Call Drupal cache clear.")
 *     ),
 *   },
 * )
 */
class CacheClear extends Base {

  /**
   * @param false $all
   */
  protected function doExecute($all = FALSE) {
    if ($all) {
      drupal_flush_all_caches();
    }
  }

}
