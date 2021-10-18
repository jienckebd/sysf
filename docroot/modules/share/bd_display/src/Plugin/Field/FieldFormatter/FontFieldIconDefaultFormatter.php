<?php

namespace Drupal\bd_display\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Template\Attribute;
use Drupal\font_field_icon\Plugin\Field\FieldFormatter\FontFieldIconDefaultFormatter as Base;

/**
 * Overrides font field formatter from font_field_icon module.
 */
class FontFieldIconDefaultFormatter extends Base {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'icon_attribute' => '',
      'link' => '',
      'link_ajax' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['icon_attribute'] = [
      '#type' => 'details',
      '#title' => t('Icon Attributes'),
      '#open' => TRUE,
    ];

    $elements['icon_attribute']['class'] = [
      '#type' => 'textfield',
      '#title' => t('Class'),
      '#default_value' => !empty($settings['icon_attribute']['class']) ? $settings['icon_attribute']['class'] : NULL,
    ];

    $elements['link'] = [
      '#type' => 'checkbox',
      '#title' => t('Link to entity'),
      '#default_value' => !empty($settings['link']) ? $settings['link'] : NULL,
    ];

    $elements['link_ajax'] = [
      '#type' => 'checkbox',
      '#title' => t('Use ajax'),
      '#default_value' => !empty($settings['link_ajax']) ? $settings['link_ajax'] : NULL,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $settings = $this->getSettings();

    $entity = $items->getEntity();

    $icon_attributes = !empty($settings['icon_attribute']) ? $settings['icon_attribute'] : [];

    if (!empty($icon_attributes['class'])) {
      $icon_attributes['class'] = explode(' ', $icon_attributes['class']);
    }
    else {
      $icon_attributes['class'] = [];
    }

    $icon_attributes['class'][] = 'fa';
    $icon_attributes = new Attribute($icon_attributes);

    $elements = [];
    foreach ($items as $delta => $item) {

      if ($settings['link']) {
        if ($settings['link_ajax']) {
          $link = $entity->toLink($item->font_field_icon_link, 'canonical', [
            'attributes' => [
              'class' => [
                'use-ajax',
              ],
              'data-dialog-type' => 'modal',
            ],
          ]);
        }
        else {
          $link = $entity->toLink($item->font_field_icon_link, 'canonical', [
            'attributes' => [
              'class' => [],
            ],
          ]);
        }
        $icon_text = $link->toString();
      }
      else {
        $icon_text = $item->font_field_icon_link;
      }

      $icon_attributes['class'][] = "fa-{$item->font_field_icon}";

      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $icon_text . "<i {$icon_attributes}></i>",
      ];
      $elements[$delta]['#attached']['library'][] = 'font_field_icon/font_field_icon';
    }
    return $elements;
  }

}
