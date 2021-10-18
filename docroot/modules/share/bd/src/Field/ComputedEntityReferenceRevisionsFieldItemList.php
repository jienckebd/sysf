<?php

namespace Drupal\bd\Field;

use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;

/**
 * Extends base class because some contrib code needs it.
 */
class ComputedEntityReferenceRevisionsFieldItemList extends EntityReferenceRevisionsFieldItemList {
  use ComputedFieldValuePluginTrait;

}
