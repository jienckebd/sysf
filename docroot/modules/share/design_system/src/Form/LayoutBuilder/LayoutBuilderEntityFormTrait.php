<?php

namespace Drupal\design_system\Form\LayoutBuilder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides logic to both defaults and overrides entity form.
 */
trait LayoutBuilderEntityFormTrait {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL) {

    $form = parent::buildForm($form, $form_state, $section_storage);
    $form['#process'][] = [$this, 'processLayoutBuilderEntityForm'];
    $form['moderation_state']['widget'][0]['#title_display'] = 'invisible';
    $form['moderation_state']['#attributes']['class'][] = 'float-left';
    $form['moderation_state']['#attributes']['class'][] = 'form-item--sm';
    $form['moderation_state']['widget'][0]['scheduled_transitions']['#access'] = FALSE;
    unset($form['moderation_state']['widget'][0]['current']);

    $form['actions']['moderation_state'] = $form['moderation_state'];
    unset($form['moderation_state']);

    $form['actions_workaround'] = $form['actions'];
    $form['actions_workaround']['#type'] = 'container';
    $form['actions_workaround']['#weight'] = 10000;
    $form['actions_workaround']['#attributes']['class'][] = 'form-actions';
    unset($form['actions']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['discard_changes'] = [
      '#type' => 'link',
      '#url' => $this->sectionStorage->getLayoutBuilderUrl('discard_changes'),
      '#title' => $this->t('Discard changes'),
    ];

    foreach ($actions as &$child) {
      if (empty($child['#type']) || !in_array($child['#type'], ['link', 'submit', 'button'])) {
        continue;
      }
      $child['#button_size'] = 'sm';
    }

    if (!empty($actions['move_sections'])) {
      $actions['move_sections']['#title'] = $this->t('Reorder rows');
    }

    $section_storage = $this->getSectionStorage();
    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();

    $actions['layout'] = [
      '#type' => 'link',
      '#title' => $this->t('Layout settings'),
      '#button_type' => 'gray',
      '#button_size' => 'sm',
      '#attributes' => [],
      '#weight' => 1000,
      '#url' => Url::fromRoute('layout_builder.settings.form', [
        'section_storage_type' => $storage_type,
        'section_storage' => $storage_id,
      ]),
    ];
    // Normalizer::attachModalAttributes($actions['layout']['#attributes'], 'layout-builder-modal');.
    $actions['submit']['#button_type'] = 'primary';
    $actions['submit']['#attributes']['class'][] = 'float-left';
    $actions['submit']['#weight'] = -1000;
    $actions['#attributes']['class'][] = 'style--dark';
    $actions['#attributes']['class'][] = 'layout-builder--dock';
    $actions['#attributes']['class'][] = 'p-1';

    // @todo move to entity form config. That's why no translation.
    $button_config_all = [
      'revert' => [
        'size' => 'md',
        'title' => 'Undo your current changes to this layout and revert back to the saved settings.',
      ],
      'discard_changes' => [
        'size' => 'md',
        'title' => 'Discard your changes to this layout.',
      ],
      'move_sections' => [
        'size' => 'fw',
        'title' => 'Reorder the rows of this layout.',
      ],
      'layout' => [
        'size' => 'fw',
        'title' => 'Configure the overall settings of this layout.',
      ],
    ];

    foreach ($button_config_all as $button_id => $button_config) {
      if (empty($actions[$button_id])) {
        continue;
      }
      $actions[$button_id]['#attributes']['class'][] = 'float-right ml-1';
      $actions[$button_id]['#modal_size'] = $button_config['size'];
      $actions[$button_id]['#button_type'] = 'darker';

      if (!empty($actions[$button_id]['#url'])) {
        // Clear any off canvas dialog settings from core.
        $actions[$button_id]['#url']->setOption('attributes', NULL);
      }

      if (isset($button_config['title'])) {
        $actions[$button_id]['#attributes']['title'] = $button_config['title'];
        $actions[$button_id]['#attributes']['data-tooltip'] = 'bottom';
      }
    }

    return $actions;
  }

  /**
   * Process callback.
   *
   * @param array $element
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array
   *   The processed form.
   */
  public function processLayoutBuilderEntityForm(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['actions_workaround']['preview_toggle']['#attributes']['class'][] = 'small';
    $element['actions_workaround']['preview_toggle']['#attributes']['class'][] = 'float-right';
    $element['actions_workaround']['preview_toggle']['#attributes']['class'][] = 'mr-2';
    $element['actions_workaround']['preview_toggle']['#weight'] = 1010;

    $element['actions_workaround']['preview_toggle']['#attributes']['title'] = $this->t('Show or hide components with their titles and configuration links.');
    $element['actions_workaround']['preview_toggle']['#attributes']['data-tooltip'] = 'bottom';
    $element['actions_workaround']['submit']['#value'] = $this->t('Save layout');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    // @todo workaround.
    // Field widgets within layout builder get their values attached to
    // layout builder form state. These properties exist both on
    // entity_view_display and entity subject.
    $this->entity->set('langcode', 'en');
    $this->entity->set('status', TRUE);

    return parent::save($form, $form_state);
  }

}
