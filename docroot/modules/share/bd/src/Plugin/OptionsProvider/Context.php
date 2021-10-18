<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for contexts.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "context",
 *   label = @Translation("Contexts"),
 *   description = @Translation("Provides contexts."),
 * )
 */
class Context extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    /** @var \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository */
    $context_repository = \Drupal::service('context.repository');

    $option = [];

    if (!$available_contexts = $context_repository->getAvailableContexts()) {
      return $option;
    }

    foreach ($context_repository->getAvailableContexts() as $context_id => $context) {
      $option[$context_id] = $context->getContextDefinition()->getLabel();
    }

    return $option;

  }

}
