<?php

namespace Drupal\design_system\Plugin\Derivative;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\layout_builder\Plugin\Derivative\ExtraFieldBlockDeriver as Base;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;

/**
 * Provides entity field block definitions for every field.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class ExtraFieldFormDeriver extends Base {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_type_labels = $this->entityTypeRepository->getEntityTypeLabels();
    foreach (\Drupal::service('entity.helper')->getDefinitions() as $entity_type_id => $entity_type) {
      // Only process fieldable entity types.
      if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        continue;
      }

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_id => $bundle) {
        $extra_fields = $this->entityFieldManager->getExtraFields($entity_type_id, $bundle_id);
        // Skip bundles without any extra fields.
        if (empty($extra_fields['form'])) {
          continue;
        }

        foreach ($extra_fields['form'] as $extra_field_id => $extra_field) {
          $derivative = $base_plugin_definition;

          $derivative['category'] = $this->t('@entity fields', ['@entity' => $entity_type_labels[$entity_type_id]]);

          $derivative['admin_label'] = $extra_field['label'];

          $context_definition = EntityContextDefinition::fromEntityType($entity_type)
            ->addConstraint('Bundle', [$bundle_id]);
          $derivative['context_definitions'] = [
            'entity' => $context_definition,
            'view_mode' => new ContextDefinition('string'),
          ];

          $derivative_id = $entity_type_id . PluginBase::DERIVATIVE_SEPARATOR . $bundle_id . PluginBase::DERIVATIVE_SEPARATOR . $extra_field_id;
          $this->derivatives[$derivative_id] = $derivative;
        }
      }
    }
    return $this->derivatives;
  }

}
