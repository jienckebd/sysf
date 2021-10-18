<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for languages.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "language",
 *   label = @Translation("Languages"),
 *   description = @Translation("Provides languages."),
 * )
 */
class Language extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    $language_manager = \Drupal::languageManager();

    $option = [];

    foreach ($language_manager->getLanguages() as $langcode => $language) {
      $option[$langcode] = $language->getName();
    }

    return $option;

  }

}
