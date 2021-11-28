<?php

namespace Drupal\design_system;

use Drupal\bd\DependencyInjection\ServiceProviderBase;
use Drupal\design_system\Extension\ThemeExtensionList;
use Drupal\design_system\Extension\ThemeHandler;
use Drupal\design_system\Render\MainContent\HtmlRenderer;

/**
 * Replace core and contrib services and provide new ones.
 */
class DesignSystemServiceProvider extends ServiceProviderBase {

  /**
   * The services to override.
   *
   * @var array
   */
  const SERVICE_OVERRIDE = [
    'alter' => [
      'html_renderer' => [
        'class' => HtmlRenderer::class,
      ],
      'extension.list.theme' => [
        'class' => ThemeExtensionList::class,
      ],
      'theme_handler' => [
        'class' => ThemeHandler::class,
      ],
    ],
  ];

}
