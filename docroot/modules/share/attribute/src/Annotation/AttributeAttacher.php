<?php

namespace Drupal\attribute\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for attribute matcher plugins.
 *
 * @ingroup attribute_plugins
 *
 * @Annotation
 */
class AttributeAttacher extends Plugin {

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
