<?php

namespace Drupal\design_system\Plugin\Derivative;

use Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;

/**
 * Provides entity field block definitions for every field.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class FieldWidgetDeriver extends FieldBlockDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_type_labels = $this->entityTypeRepository->getEntityTypeLabels();
    foreach ($this->entityFieldManager->getFieldMap() as $entity_type_id => $entity_field_map) {
      foreach ($entity_field_map as $field_name => $field_info) {
        // Skip fields without any formatters.
        $options = $this->formatterManager->getOptions($field_info['type']);
        if (empty($options)) {
          continue;
        }

        foreach ($field_info['bundles'] as $bundle) {
          $derivative = $base_plugin_definition;
          $field_definition = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle)[$field_name];

          // Store the default formatter on the definition.
          $derivative['default_formatter'] = '';
          $field_type_definition = $this->fieldTypeManager->getDefinition($field_info['type']);
          if (isset($field_type_definition['default_widget'])) {
            $derivative['default_widget'] = $field_type_definition['default_widget'];
          }

          $derivative['category'] = $this->t('@entity fields', ['@entity' => $entity_type_labels[$entity_type_id]]);

          $derivative['admin_label'] = $field_definition->getLabel();

          // Add a dependency on the field if it is configurable.
          if ($field_definition instanceof FieldConfigInterface) {
            $derivative['config_dependencies'][$field_definition->getConfigDependencyKey()][] = $field_definition->getConfigDependencyName();
          }
          // For any field that is not display configurable, mark it as
          // unavailable to place in the block UI.
          $derivative['_block_ui_hidden'] = !$field_definition->isDisplayConfigurable('view');

          $context_definition = EntityContextDefinition::fromEntityTypeId($entity_type_id)->setLabel($entity_type_labels[$entity_type_id]);
          $context_definition->addConstraint('Bundle', [$bundle]);
          $derivative['context_definitions'] = [
            'entity' => $context_definition,
            'view_mode' => new ContextDefinition('string'),
          ];

          $derivative_id = $entity_type_id . PluginBase::DERIVATIVE_SEPARATOR . $bundle . PluginBase::DERIVATIVE_SEPARATOR . $field_name;
          $this->derivatives[$derivative_id] = $derivative;
        }
      }
    }
    return $this->derivatives;
  }

}
