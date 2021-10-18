<?php

namespace Drupal\bd\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the label is unique within entity type and optionally bundle.
 *
 * @Constraint(
 *   id = "UniqueEntityLabel",
 *   label = @Translation("Unique Entity Label", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueEntityLabel extends Constraint {

  /**
   * The message that will be shown if the value is not unique.
   */
  public $notUnique = 'The label %value is already used by another %entity_type_label. Please use another label.';

}
