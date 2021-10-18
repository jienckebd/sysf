<?php

namespace Drupal\bd\Entity\ListBuilder;

use Drupal\field_ui\FieldConfigListBuilder as Base;

/**
 * Extends field config list builder.
 */
class FieldConfigListBuilder extends Base {

  /**
   * {@inheritdoc}
   */
  public function render($target_entity_type_id = NULL, $target_bundle = NULL) {
    $build_field_config = parent::render($target_entity_type_id, $target_bundle);

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'field-config--overview',
        ],
      ],
    ];

    $build['field_config'] = [
      '#type' => 'container',
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Storable fields'),
      ],
      'list' => $build_field_config,
    ];

    $build['bundle_field_definition'] = $this->buildSummaryTable('bundle_field_definition', $target_entity_type_id, $target_bundle);
    $build['base_field_override'] = $this->buildSummaryTable('base_field_override', $target_entity_type_id, $target_bundle);
    $build['entity_field_group'] = $this->buildSummaryTable('entity_field_group', $target_entity_type_id, $target_bundle);

    return $build;
  }

  /**
   * @param $entity_type_id
   * @param $target_entity_type_id
   * @param $target_bundle_id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildSummaryTable($entity_type_id, $target_entity_type_id, $target_bundle_id) {

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_storage_entity_field_group = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_list_builder_entity_field_group = $this->entityTypeManager->getListBuilder($entity_type_id);

    $entity_type_label_plural = $entity_type->getPluralLabel();

    $entities_entity_field_group = $entity_storage_entity_field_group->loadByProperties([
      'entity_type' => $target_entity_type_id,
      'bundle' => $target_bundle_id,
    ]);

    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'description' => $this->t('Description'),
      'operations' => $this->t('Operations'),
    ];

    $rows = [];
    foreach ($entities_entity_field_group as $entity_id => $entity) {
      $row = [];

      $row['id'] = $entity->id();
      $row['label'] = $entity->label();
      $row['description'] = $entity->get('description');
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $entity_list_builder_entity_field_group->getOperations($entity),
        ],
      ];

      $rows[$entity_id] = $row;
    }

    $table = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are not yet any @entity_type_label_plural.', [
        '@entity_type_label_plural' => $entity_type_label_plural,
      ]),
    ];

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'mb-4',
        ],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $entity_type_label_plural,
      ],
      'list' => $table,
    ];

    return $build;
  }

}
