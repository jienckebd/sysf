<?php

namespace Drupal\bd\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a plugin annotation.
 *
 * @Annotation
 */
class Base extends Plugin {

  /**
   * A unique identifier for the plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
