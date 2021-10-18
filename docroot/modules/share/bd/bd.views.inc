<?php

/**
 * @file
 * Contains views API hooks for Flag module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function bd_views_data_alter(array &$data) {
  $entity_helper = \Drupal::service('entity.helper');

  foreach ($entity_helper->getDefinitions() as $entity_type) {

    $entity_type_id = $entity_type->id();
    if (!$entity_type->hasHandlerClass('views_data')) {
      continue;
    }

    $base_table = $entity_helper->getHandler($entity_type_id, 'views_data')->getViewsTableForEntityType($entity_type);
    $data[$base_table]['related_relevancy'] = [
      'title' => t('@entity_label entity relationship relevancy', ['@entity_label' => $entity_type->getLabel()]),
      'help' => t('Sort by the relatedness to a given entity.'),
      'sort' => [
        'id' => 'entity_relationship_relevancy',
        'field' => $entity_type->getKey('id'),
      ],
    ];

    $data[$base_table]['not_current_entity'] = [
      'title' => t('@entity_label not current entity', ['@entity_label' => $entity_type->getLabel()]),
      'help' => t('Filters out the current entity.'),
      'filter' => [
        'id' => 'not_current_entity',
        'field' => $entity_type->getKey('id'),
      ],
    ];

    $data[$base_table]['entity_context'] = [
      'title' => t('@entity_label related to entity context', ['@entity_label' => $entity_type->getLabel()]),
      'help' => t('Relates to an entity context.'),
      'filter' => [
        'id' => 'entity_context',
        'field' => $entity_type->getKey('id'),
      ],
    ];
  }

  $data['search_api_index_content']['entity_queue'] = [
    'title' => t('Entity Queue'),
    'help' => t('todo.'),
    'relationship' => [
      'id' => 'search_api_relationship',
      'title' => t('Entity Queue'),
      'label' => t('Entity Queue'),
      'base' => 'entity_subqueue__items',
      'base field' => 'items_target_id',
      'real field' => 'item_id',
      'relationship field' => 'item_id',
      'extra' => [
        [
          'table' => 'file_usage',
          'field' => 'type',
          'operator' => '=',
          'value' => 'taxonomy_term',
        ],
      ],
    ],
    'sort' => [
      'id' => 'entity_queue_position',
      'group' => t('Entityqueue'),
      'title' => t('@target_label Queue Position', [
        '@target_label' => 'Content',
      ]),
      'label' => t('@target_label Queue Position', [
        '@target_label' => 'Content',
      ]),
      'help' => t('Position of item in the @target_label queue.', [
        '@target_label' => 'Content',
      ]),
      'field' => 'delta',
      'field table' => 'entity_subqueue__items',
      'field_name' => 'items',
    ],
  ];

}

/**
 * Implements hook_field_views_data_alter().
 */
function bd_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  $field_type = $field_storage->getType();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $field_name = $field_storage->getName();

  if ($field_type == 'daterange') {
    foreach ($data as $table => &$table_fields) {

      $filter_id = "{$field_name}__dmto";

      $table_fields[$filter_id] = [
        'title' => t('Date Range (Dynamic Many to One)'),
        'group' => t('Content'),
        'filter' => [
          'id' => 'dynamic_many_to_one',
          'field' => 'field_date_range_value',
          'table' => 'paragraph__field_date_range',
          'field_name' => $field_name,
        ],
      ];
    }
  }

}
