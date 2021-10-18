<?php

namespace Drupal\bd\Field;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList;

/**
 * Extends base class because some contrib code needs it.
 */
class ComputedDynamicEntityReferenceFieldItemList extends DynamicEntityReferenceFieldItemList {
  use ComputedFieldValuePluginTrait;

}
