<?php

namespace Drupal\autoref\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for autoref matcher plugins.
 *
 * @ingroup autoref_plugins
 *
 * @Annotation
 */
class AutorefMatcher extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin title.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title = '';

}
