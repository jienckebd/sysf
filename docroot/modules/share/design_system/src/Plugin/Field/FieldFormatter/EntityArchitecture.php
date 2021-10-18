<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceFormatterTrait;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_architecture",
 *   label = @Translation("Entity architecture"),
 *   description = @Translation("Render the entity field summary of an entity
 *   type."), field_types = {
 *     "dynamic_entity_reference",
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EntityArchitecture extends EntityReferenceEntityFormatter {

  use DynamicEntityReferenceFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $entity_storage_field_config = $this->entityHelper->getStorage('field_config');

    $header = [
      'field_name' => $this->t('Field name'),
      'description' => $this->t('Description'),
      'type' => $this->t('Field type'),
      'required' => $this->t('Required'),
      'cardinality' => $this->t('Number of values'),
    ];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {

      // In case of dynamic_entity_reference.
      $target_entity_type_id = $entity->getEntityType()->getBundleOf();
      $target_bundle_id = $entity->id();

      $entities_field_config = $entity_storage_field_config->loadByProperties([
        'entity_type' => $target_entity_type_id,
        'bundle' => $target_bundle_id,
      ]);

      if (empty($entities_field_config)) {
        continue;
      }

      $rows = [];

      /**
       * @var string $field_name
       * @var \Drupal\field\FieldConfigInterface $field_config
       */
      foreach ($entities_field_config as $field_name => $field_config) {

        $cardinality = $field_config->getFieldStorageDefinition()->getCardinality();

        $row = [];
        $row['field_name'] = $field_config->getLabel();
        $row['description'] = $field_config->getDescription();
        $row['type'] = $field_config->getType();
        $row['required'] = $field_config->isRequired() ? $this->t('Yes') : $this->t('No');
        $row['cardinality'] = ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) ? $this->t('Unlimited') : $cardinality;
        $rows[] = $row;
      }

      $build = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => [
          'class' => [
            'table-striped',
          ],
        ],
      ];

      $elements[$delta] = $build;

    }

    return $elements;
  }

}
