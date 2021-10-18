<?php

namespace Drupal\bd\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Provides a local action class to redirect to current route.
 */
class RedirectToCurrent extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);

    $options['query']['destination'] = Url::fromRoute("<current>")->toString();

    return $options;
  }

}
