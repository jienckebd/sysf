<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\language\Plugin\Block\LanguageBlock as Base;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;

/**
 * Extends language switcher block from core.
 *
 * @Block(
 *   id = "language_block",
 *   admin_label = @Translation("Language switcher"),
 *   category = @Translation("System"),
 *   deriver = "Drupal\language\Plugin\Derivative\LanguageBlock"
 * )
 */
class LanguageBlock extends Base implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $type = $this->getDerivativeId();
    $links = $this->languageManager->getLanguageSwitchLinks($type, Url::fromRoute($route_name));
    $langcode_active = $this->languageManager->getCurrentLanguage()->getId();

    if (empty($links->links)) {
      return [];
    }

    $build = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => [
        'class' => [
          'language-switcher',
        ],
      ],
    ];

    foreach ($links->links as $langcode => $link) {

      if ($langcode == $langcode_active) {
        continue;
      }

      $item = [];

      $item['#type'] = 'link';
      $item['#attributes']['data-dialog-type'] = 'ajax_history';
      $item['#attributes']['class'][] = 'use-ajax';
      $item['#attributes']['class'][] = 'mb-2';

      $flagcode = design_system_langcode_to_flagcode($langcode);

      $item['#title'] = [
        'flag' => [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#value' => '',
          '#attributes' => [
            'src' => "/themes/custom/alpha/node_modules/flag-icon-css/flags/4x3/{$flagcode}.svg",
            'class' => [
              'flag',
              'flag--sm',
              'mr-1',
              'd-inline-block',
              'align-middle',
            ],
          ],
        ],
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t($link['title'], [], [
            'langcode' => $langcode,
          ]),
          '#attributes' => [
            'class' => [
              'd-inline-block',
              'align-middle',
            ],
          ],
        ],
      ];

      /** @var \Drupal\Core\Url $url */
      $url = $link['url'];
      $url->setOption('language', $link['language']);

      $item['#url'] = $url;
      $build['#items'][$langcode] = $item;
    }

    return $build;
  }

  /**
   * The language module makes block non-cacheable. We need cacheable.
   *
   * @return int
   *   The cache max age.
   */
  public function getCacheMaxAge() {
    return -1;
  }

}
