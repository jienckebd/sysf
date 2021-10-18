<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for entity fields.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "entity_field",
 *   label = @Translation("Entity fields"),
 *   description = @Translation("Entity fields of an entity type."),
 * )
 */
class EntityField extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $option = [];

    $entity_type_id = $this->configuration['entity_type'];
    $bundle = $this->configuration['bundle'] ?? NULL;

    if (!empty($bundle)) {
      $definitions = $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle);
    }
    else {
      $definitions = $entity_field_manager->getBaseFieldDefinitions($entity_type_id);
    }

    foreach ($definitions as $field_definition) {
      $option[$field_definition->getName()] = $field_definition->getLabel();
    }

    return $option;

  }

}
