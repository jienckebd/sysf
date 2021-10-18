<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "button_group",
 *   label = @Translation("Button group"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ButtonGroup extends Base implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'dropbutton' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['dropbutton'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make dropbutton'),
      '#default_value' => $this->getSetting('dropbutton'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (!$items->count()) {
      return NULL;
    }

    $entity = $items->getEntity();

    if ($this->getSetting('dropbutton')) {

      $toggle_type = 'light';
      if ($entity->hasField('field_button_type')) {
        $toggle_type = $entity->field_button_type->value ?: $toggle_type;
      }

      $build = [
        '#type' => 'operations',
        '#primary_button_type' => NULL,
        '#primary_button_size' => NULL,
        '#tooltip' => $this->t('More options'),
        '#toggle_type' => $toggle_type,
        '#attributes_dropdown' => [],
        '#attributes_button' => [],
        '#attributes_toggle' => [],
      ];
    }
    else {
      $build = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'btn-group',
          ],
        ],
      ];
    }

    $links = [];
    foreach ($items as $delta => $field_item) {
      $related_entity = $field_item->entity;
      $related_entity_build = \Drupal::service('entity.helper')->getViewBuilder($related_entity->getEntityTypeId())->view($related_entity);
      $links[] = [
        'title' => $related_entity_build,
      ];
    }

    if ($this->getSetting('dropbutton')) {
      $build['#links'] = $links;
    }
    else {
      foreach ($links as $link) {
        $build[] = $link;
      }
    }

    return $build;
  }

}
