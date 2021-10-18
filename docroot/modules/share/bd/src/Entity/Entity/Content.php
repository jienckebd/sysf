<?php

namespace Drupal\bd\Entity\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;

/**
 * Provides a generic normalized entity class.
 */
class Content extends EditorialContentEntityBase implements ContentInterface {

  use NormalizedContentEntityTrait;

}
