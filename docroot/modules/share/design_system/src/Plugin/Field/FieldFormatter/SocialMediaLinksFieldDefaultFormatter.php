<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\social_media_links_field\Plugin\Field\FieldFormatter\SocialMediaLinksFieldDefaultFormatter as Base;

/**
 * Extends social_media links formatter.
 */
class SocialMediaLinksFieldDefaultFormatter extends Base {

  /**
   * The social media colors.
   */
  const MAP_SOCIAL_COLOR = [
    'facebook' => [
      'bg' => '4267B2',
      'text' => 'ffffff',
    ],
    'snapchat' => [
      'bg' => 'FFFC00',
      'text' => '000000',
    ],
    'instagram' => [
      'bg' => 'f2a900',
      'text' => 'ffffff',
    ],
    'youtube' => [
      'bg' => 'FF0000',
      'text' => '282828',
    ],
    'twitter' => [
      'bg' => '1DA1F2',
      'text' => 'ffffff',
    ],
    'linkedin' => [
      'bg' => '2867B2',
      'text' => 'ffffff',
    ],
    'print' => [
      'bg' => '333333',
      'text' => 'ffffff',
    ],
    'email_this' => [
      'bg' => '666666',
      'text' => 'ffffff',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $entity = $items->getEntity();

    // Process tokens before getting build.
    $token = \Drupal::token();
    foreach ($items as $delta => $field_item) {
      if (!$platform_values = $field_item->platform_values) {
        continue;
      }

      foreach ($platform_values as $platform_id => &$platform_value) {
        if (!empty($platform_value['value'])) {
          if ($token->scan($platform_value['value'])) {
            $platform_value['value'] = $token->replace($platform_value['value'], [], ['clear' => TRUE]);
          }
        }
      }

      $field_item->platform_values = $platform_values;
    }

    $element = parent::viewElements($items, $langcode);

    if (empty($element['#platforms'])) {
      return $element;
    }

    $build = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => [
        'class' => [
          'social-share',
          'social-media-links--platforms',
          'text-center',
          'd-flex',
        ],
      ],
    ];

    if ($entity->hasField('field_justify') && $justify = $entity->field_justify->value) {
      $build['#attributes']['class'][] = "justify-content-{$justify}";
    }

    foreach ($element['#platforms'] as $platform_id => $platform_config) {

      $color_bg = !empty(static::MAP_SOCIAL_COLOR[$platform_id]) ? static::MAP_SOCIAL_COLOR[$platform_id]['bg'] : 'primary';
      $color_fg = !empty(static::MAP_SOCIAL_COLOR[$platform_id]) ? static::MAP_SOCIAL_COLOR[$platform_id]['text'] : 'white';

      $build['#items'][$platform_id] = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => $platform_config['element'],
        '#attributes' => [
          'href' => $platform_config['url'],
          'class' => [
            'social-share--link',
            'mr-1',
            'd-flex',
            'justify-content-center',
            'align-items-center',
            'position-relative',
            'hover--darken',
          ],
          'style' => [
            'background-color' => "#{$color_bg}",
            'color' => "#{$color_fg}",
            'width' => '36px',
            'height' => '36px',
          ],
        ],
      ];

      if (!in_array($platform_id, ['print'])) {
        $build['#items'][$platform_id]['#attributes']['target'] = '_blank';
      }

      if (!empty($platform_config['attributes']['title'])) {
        $build['#items'][$platform_id]['#attributes']['title'] = $platform_config['attributes']['title'];
        $build['#items'][$platform_id]['#attributes']['data-tooltip'] = 'top';
      }
    }

    return $build;
  }

}
