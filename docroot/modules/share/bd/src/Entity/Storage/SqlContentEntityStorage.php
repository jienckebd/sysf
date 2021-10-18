<?php

namespace Drupal\bd\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage as Base;

/**
 * Common storage handler for content entities.
 */
class SqlContentEntityStorage extends Base implements SqlContentEntityStorageInterface {
  use SqlContentEntityStorageTrait;

}
