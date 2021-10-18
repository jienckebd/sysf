<?php

namespace Drupal\bd\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the value is unique within entity type.
 *
 * @Constraint(
 *   id = "UniquePropertyValue",
 *   label = @Translation("Unique property value", context = "Validation"),
 *   type = "string"
 * )
 */
class UniquePropertyValue extends Constraint {

  /**
   * The property to check.
   *
   * @var string
   */
  public $propertySelector;

  /**
   * The message that will be shown if the value is not unique.
   */
  public $notUnique = 'The value %value for %property_name is already used by another %entity_type_label.';

}
