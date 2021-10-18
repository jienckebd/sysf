<?php

namespace Drupal\bd\Config;

use Drupal\Core\Config\Config as Base;

/**
 * Makes core config accessible as array.
 */
class Config extends Base implements \ArrayAccess, \Iterator {

  use ConfigTrait;

}
