<?php

namespace Drupal\design_system\Form\EntityDisplay;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Extends field_ui and layout_builder.
 */
trait EntityDisplayFormTrait {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\bd\Entity\EntityHelper $entity_helper */
    $entity_helper = \Drupal::service('entity.helper');

    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $entity */
    $entity = $this->entity;
    $entity_type_id = $entity->getEntityTypeId();
    $target_entity_type_id = $entity->getTargetEntityTypeId();
    $target_entity_type = $entity_helper->getDefinition($target_entity_type_id);
    $target_bundle_id = $entity->getTargetBundle();
    $mode_id = $this->entity->getMode();
    $display_context_id = ($entity_type_id == 'entity_view_display') ? 'view' : 'form';
    $bundle_display_config = $entity_helper->getBundleConfig($target_entity_type, $target_bundle_id, 'display');

    $layout_builder_enabled = $bundle_display_config[$display_context_id]['mode'][$mode_id]['use_layout_builder'] ?? FALSE;

    if ($entity_type_id == 'entity_form_display') {
      $mode_id_check = "form__{$mode_id}";
    }
    else {
      $mode_id_check = $mode_id;
    }

    $entity_type_id_bundle = $target_entity_type->getBundleEntityType();

    $entity_type_id_mode = 'entity_view_mode';

    $entity_view_modes = $entity_helper->getStorage($entity_type_id_mode)->loadByProperties([
      'targetEntityType' => $target_entity_type_id,
    ]);

    // Hide core elements.
    $form['modes']['#access'] = FALSE;
    if ($layout_builder_enabled) {
      $form['fields']['#access'] = FALSE;
    }

    if (!empty($form['manage_layout'])) {
      $form['manage_layout']['#access'] = FALSE;
    }
    if (!empty($form['layout'])) {
      $form['layout']['#access'] = FALSE;
    }

    $form['general'] = [
      '#type' => 'vertical_tabs',
      '#tree' => TRUE,
      '#weight' => -1000,
    ];

    $form['overview'] = [
      '#type' => 'details',
      '#title' => $this->t('Overview'),
      '#tree' => TRUE,
      '#group' => 'general',
    ];

    $header_mode = [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'use_layout_builder' => $this->t('Use layout builder'),
      'allow_custom' => $this->t('Allow overrides'),
      'inherit' => $this->t('Inherit'),
      'reset' => $this->t('Reset'),
      'manage' => $this->t('Layout builder'),
      'weight' => $this->t('Weight'),
    ];

    $form['overview']['mode'] = [
      '#type' => 'table',
      '#header' => $header_mode,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'entity-display-mode-order-weight',
        ],
      ],
    ];

    $modes = [];

    if ($entity_type_id == 'entity_view_display') {
      $mode = [];
      $mode['id'] = 'default';
      $mode['label'] = $this->t('Default');
      $mode['use_layout_builder'] = TRUE;
      $mode['allow_custom'] = TRUE;
      $mode['inherit'] = NULL;
      $mode['weight'] = 0;
      $modes['default'] = $mode;
    }

    foreach ($entity_view_modes as $entity_id => $entity) {
      $mode_id = str_replace("{$target_entity_type_id}.", '', $entity_id);
      $mode = [];
      $mode['id'] = $entity->id();
      $mode['label'] = $entity->label();
      $mode['use_layout_builder'] = TRUE;
      $mode['allow_custom'] = TRUE;
      $mode['inherit'] = NULL;
      $mode['weight'] = 0;
      $modes[$mode_id] = $mode;
    }

    foreach ($modes as $mode_id => $mode) {

      if ($entity_type_id == 'entity_form_display') {
        if (!fnmatch("form__*", $mode_id)) {
          continue;
        }
      }
      else {
        if (fnmatch("form__*", $mode_id)) {
          continue;
        }
      }

      $option_mode = [];

      $view_mode_id = str_replace("{$target_entity_type_id}.", "", $mode_id);

      $url_layout_builder = Url::fromRoute("layout_builder.defaults.{$target_entity_type_id}.view", [
        $entity_type_id_bundle => $target_bundle_id,
        'view_mode_name' => $view_mode_id,
      ]);

      $url_layout_builder_reset = Url::fromRoute("layout_builder.defaults.{$target_entity_type_id}.view", [
        $entity_type_id_bundle => $target_bundle_id,
        'view_mode_name' => $view_mode_id,
      ]);

      $option_mode['id'] = [
        '#markup' => $mode['id'],
      ];
      $option_mode['label'] = [
        '#markup' => $mode['label'],
      ];

      $option_mode['use_layout_builder'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use layout builder'),
        '#default_value' => $entity_helper->getBundleConfig($target_entity_type, $target_bundle_id, "display.{$display_context_id}.mode.{$mode_id}.use_layout_builder", FALSE),
      ];

      $option_mode['allow_custom'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow override'),
        '#default_value' => $entity_helper->getBundleConfig($target_entity_type, $target_bundle_id, "display.{$display_context_id}.mode.{$mode_id}.allow_custom", FALSE),
      ];
      $option_mode['inherit'] = [
        '#type' => 'select',
        '#normalize' => TRUE,
        '#title' => $this->t('Inherit'),
        '#options_provider' => [
          'plugin_id' => 'entity_list',
          'plugin_config' => [
            'entity_type' => 'entity_view_display',
            'load_properties' => [
              'targetEntityType' => $target_entity_type_id,
            ],
          ],
        ],
        '#default_value' => $entity_helper->getBundleConfig($target_entity_type, $target_bundle_id, "display.{$display_context_id}.mode.{$mode_id}.inherit"),
      ];
      $option_mode['reset'] = [
        '#type' => 'link',
        '#url' => $url_layout_builder_reset,
        '#title' => $this->t('Reset'),
      ];
      $option_mode['manage'] = [
        '#type' => 'link',
        '#url' => $url_layout_builder,
        '#title' => $this->t('Manage layout'),
      ];
      $option_mode['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $mode_id]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $entity_helper->getBundleConfig($target_entity_type, $target_bundle_id, "display.{$display_context_id}.mode.{$mode_id}.weight", 0),
        '#attributes' => ['class' => ['entity-display-mode-order-weight']],
      ];

      $option_mode['#weight'] = $option_mode['weight']['#default_value'];
      $option_mode['#attributes']['class'][] = 'draggable';

      $form['overview']['mode'][$mode_id] = $option_mode;
    }

    uasort($form['overview']['mode'], 'Drupal\Component\Utility\SortArray::sortByWeightProperty');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $entity */
    $entity = $this->entity;

    /** @var \Drupal\bd\Entity\EntityHelper $entity_helper */
    $entity_helper = \Drupal::service('entity.helper');

    $entity_type_id = $entity->getEntityTypeId();

    $target_entity_type_id = $entity->getTargetEntityTypeId();
    $target_bundle_id = $entity->getTargetBundle();

    $entity_storage_entity_view_display = $entity_helper->getStorage('entity_view_display');

    $values = $form_state->getValues();
    $display_context_id = ($entity_type_id == 'entity_view_display') ? 'view' : 'form';

    $config_data_mode = $values['overview']['mode'];
    $mode_config_key = "display.{$display_context_id}.mode";
    $entity_helper->setBundleConfig($target_entity_type_id, $target_bundle_id, $mode_config_key, $config_data_mode);

    foreach ($config_data_mode as $mode_id => $mode_config) {

      $entity_view_display_id = "{$target_entity_type_id}.{$target_bundle_id}.{$mode_id}";
      $save_entity_view_display = FALSE;

      if ($entity_view_display = $entity_storage_entity_view_display->load($entity_view_display_id)) {

        if ($entity_view_display->getThirdPartySetting('layout_builder', 'enabled') != $mode_config['use_layout_builder']) {
          $save_entity_view_display = TRUE;
          $entity_view_display->setThirdPartySetting('layout_builder', 'enabled', $mode_config['use_layout_builder']);
        }

        if ($entity_view_display->getThirdPartySetting('layout_builder', 'allow_custom') != $mode_config['allow_custom']) {
          $save_entity_view_display = TRUE;
          $entity_view_display->setThirdPartySetting('layout_builder', 'allow_custom', $mode_config['allow_custom']);
        }

        if ($save_entity_view_display) {
          $entity_view_display->save();

          // Set entity on form so other modules will use this.
          if ($this->entity->uuid() == $entity_view_display->uuid()) {
            $this->entity = $entity_view_display;
          }
        }

      }

    }

    // \Drupal::classResolver(EntityDisplayRebuilder::class)->rebuildEntityTypeDisplays($target_entity_type_id, $target_bundle_id);
  }

  /**
   * Builds the table row structure for a single field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A table row array.
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    if ($this->entity->isLayoutBuilderEnabled()) {
      return [];
    }
    return parent::buildFieldRow($field_definition, $form, $form_state);
  }

}
