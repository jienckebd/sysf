<?php

namespace Drupal\bd\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * Provides generic computed field plugin for address, geo, text, etc fields.
 */
class ComputedFieldValueGenericFieldItemList extends FieldItemList {
  use ComputedFieldValuePluginTrait;

}
