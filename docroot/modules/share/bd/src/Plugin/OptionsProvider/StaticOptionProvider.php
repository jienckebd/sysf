<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for static values.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "static",
 *   label = @Translation("Static"),
 *   description = @Translation("Provides static list of options."),
 * )
 */
class StaticOptionsProvider extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    $option = [];
    foreach ($this->configuration as $key => $value) {
      $option[$key] = $value['label'];
    }

    return $option;

  }

}
