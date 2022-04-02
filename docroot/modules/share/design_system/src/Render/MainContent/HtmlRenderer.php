<?php

namespace Drupal\design_system\Render\MainContent;

use Drupal\Core\Render\MainContent\HtmlRenderer as Base;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extends core HTML renderer.
 */
class HtmlRenderer extends Base {

  /**
   * {@inheritDoc}
   */
  protected function prepare(array $main_content, Request $request, RouteMatchInterface $route_match) {
  }

}
