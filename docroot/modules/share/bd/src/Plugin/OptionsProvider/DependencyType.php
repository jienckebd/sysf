<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for dependency types.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "dependency_type",
 *   label = @Translation("Dependency type"),
 * )
 */
class DependencyType extends EntityList {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $this->configuration['entity_type'] = 'taxonomy_term';
    $this->configuration['bundle'] = 'dependency_type';
    return parent::getPossibleOptions($account);
  }

}
