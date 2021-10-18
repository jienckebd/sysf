<?php

namespace Drupal\bd\PluginDefinition;

use Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator;
use Drupal\bd\Php\Obj;

/**
 * Provides a plugin definition based on an object.
 */
class ObjectPluginDefinitionDecorator extends ArrayPluginDefinitionDecorator {

  /**
   * {@inheritdoc}
   */
  public static function createFromDecoratedDefinition($decorated_plugin_definition) {

    if (is_object($decorated_plugin_definition)) {
      $decorated_plugin_definition = Obj::toArray($decorated_plugin_definition);
    }

    return parent::createFromDecoratedDefinition($decorated_plugin_definition);
  }

}
