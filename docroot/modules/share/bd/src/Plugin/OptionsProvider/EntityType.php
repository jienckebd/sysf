<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for entity types.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "entity_type",
 *   label = @Translation("Entity types"),
 *   description = @Translation("Entity types optionally filtered by tag."),
 * )
 */
class EntityType extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    $option = [];

    $entity_type_tag = $this->configuration['tag'] ?? NULL;
    if (!empty($entity_type_tag)) {
      $definitions = $this->entityHelper->getDefinitionsByTag($entity_type_tag);
    }
    else {
      $definitions = $this->entityHelper->getDefinitions();
    }

    foreach ($definitions as $entity_type_id => $entity_type) {
      $option[$entity_type_id] = $entity_type->getLabel();
    }

    return $option;

  }

}
