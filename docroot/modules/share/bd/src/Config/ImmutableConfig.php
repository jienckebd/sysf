<?php

namespace Drupal\bd\Config;

use Drupal\Core\Config\ImmutableConfig as Base;

/**
 * Makes core config accessible as array.
 */
class ImmutableConfig extends Base implements \ArrayAccess, \Iterator {

  use ConfigTrait;

}
