<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for an entity list.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "entity_list",
 *   label = @Translation("Entity list"),
 *   description = @Translation("Provides a set of entities."),
 * )
 */
class EntityList extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    $entity_type_id = $this->configuration['entity_type'];
    $bundle_id = isset($this->configuration['bundle']) ? $this->configuration['bundle'] : NULL;
    $load_properties = isset($this->configuration['load_properties']) ? $this->configuration['load_properties'] : [];

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    $entity_storage = $this->entityHelper->getStorage($entity_type_id);

    if (!empty($bundle_id)) {
      $bundle_key = $entity_type->getKey('bundle');
      $load_properties[$bundle_key] = $bundle_id;
    }

    if (!empty($load_properties)) {
      $entities = $entity_storage->loadByProperties($load_properties);
    }
    else {
      $entities = $entity_storage->loadMultiple();
    }

    $option = [];

    foreach ($entities as $entity_id => $entity) {
      $label = $entity->label();
      $id = $entity->id();
      $option[$entity_id] = "{$label} ({$id})";
    }

    return $option;

  }

}
