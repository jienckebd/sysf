<?php

namespace Drupal\bd\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Extends base class because some contrib code needs it.
 */
class ComputedEntityReferenceFieldItemList extends EntityReferenceFieldItemList {
  use ComputedFieldValuePluginTrait;

}
