<?php

/**
 * @file
 * Provide views data for design_system.
 */

/**
 * Implements hook_views_data().
 */
function design_system_views_data() {

  $data['views']['component'] = [
    'title' => t('Component'),
    'help' => t('Displays a component.'),
    'area' => [
      'id' => 'component',
    ],
  ];

  return $data;
}
