<?php

namespace Drupal\bd\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for a configurable Search plugin.
 */
interface EntityPluginInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface {
}
