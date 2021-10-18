<?php

namespace Drupal\content_moderation_scheduled_updates\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures scheduled publishing jobs contain valid transitions.
 *
 * @Constraint(
 *   id = "CmsuScheduledStateTransition",
 *   label = @Translation("Is a valid target moderation state", context = "Validation")
 * )
 */
class CmsuScheduledStateTransition extends Constraint {

  public $messageInvalidTransition = 'The update scheduled on %date is not valid as %from cannot change to %to.';

}
