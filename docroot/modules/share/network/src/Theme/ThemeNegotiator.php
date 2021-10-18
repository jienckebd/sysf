<?php

namespace Drupal\network\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Tests the theme negotiation functionality.
 *
 * Retrieves the theme key of the theme to use for the current request based on
 * the theme name provided in the URL.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (!$route_object = $route_match->getRouteObject()) {
      return FALSE;
    }
    if ($route_object->getOption('_admin_route')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {

    $theme_name = "autotheme__14";

    return $theme_name;
  }

}
