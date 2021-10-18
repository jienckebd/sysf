<?php

namespace Drupal\bd\Plugin\DataDeriver;

use Drupal\bd\Plugin\EntityPluginBase;

/**
 * Derives data from an entity collection.
 *
 * @DataDeriver(
 *   plugin_type = "data_deriver",
 *   id = "clone_entity",
 *   label = @Translation("Clone Entity"),
 *   help = @Translation("Clone a specified set of entities."),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", required = TRUE, label = @Translation("Entity"))
 *   }
 * )
 */
class CloneEntity extends EntityPluginBase {

  /**
   * @param array $data
   * @param array $context
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(array &$data = [], array &$context = []) {

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity_parent = $context['entity'];

    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $context['field_config'];

    $entity_type_id = $field_config->getSetting('target_type');
    $entity_id = $this->configuration['base_entity'];

    $original_entity = \Drupal::service('entity.helper')
      ->getStorage($entity_type_id)
      ->load($entity_id);

    if (empty($original_entity)) {
      return [];
    }

    $new_entity = $original_entity->createDuplicate();

    // @todo provide config option to duplicate references too.
    if ($new_entity->hasField('field_region')) {

      $field_items_region = $new_entity->get('field_region');

      if (!$field_items_region->isEmpty()) {
        foreach ($field_items_region as $field_item) {

          $region = $field_item->entity;
          $new_region = $region->createDuplicate();
          $field_item->setValue([
            'entity' => $new_region,
            'target_id' => NULL,
          ]);

        }
      }

    }

    $derived = [];

    $derived[] = [
      'entity' => $new_entity,
      'target_id' => NULL,
    ];

    return $derived;
  }

}
