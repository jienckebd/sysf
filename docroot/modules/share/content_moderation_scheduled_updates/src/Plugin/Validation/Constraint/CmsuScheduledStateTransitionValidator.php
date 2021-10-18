<?php

namespace Drupal\content_moderation_scheduled_updates\Plugin\Validation\Constraint;

use Drupal\content_moderation_scheduled_updates\CmsuUtilityInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\scheduled_updates\ScheduledUpdateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if a moderation state transition is valid.
 */
class CmsuScheduledStateTransitionValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The moderation info.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * CMSU utilities.
   *
   * @var \Drupal\content_moderation_scheduled_updates\CmsuUtilityInterface
   */
  protected $cmsuUtility;

  /**
   * Creates a new CmsuScheduledStateTransitionValidator instance.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   The moderation information.
   * @param \Drupal\content_moderation_scheduled_updates\CmsuUtilityInterface
   *   CMSU utilities.
   */
  public function __construct(ModerationInformationInterface $moderationInformation, CmsuUtilityInterface $cmsuUtility) {
    $this->moderationInformation = $moderationInformation;
    $this->cmsuUtility = $cmsuUtility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('cmsu.utility')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\content_moderation_scheduled_updates\Plugin\Validation\Constraint\CmsuScheduledStateTransition $constraint */
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $value->getEntity();

    // A list of states.
    // Each value is an array with keys time and state.
    $stateTimeline = [];

    $newStateAfterSave = $entity->moderation_state->value ?? NULL;
    if ($newStateAfterSave) {
      $stateTimeline[] = ['time' => 0, 'state' => $newStateAfterSave];
    }

    if ($transitions = $this->getScheduledStateTransitions($entity)) {
      array_push($stateTimeline, ...$transitions);
    }

    usort($stateTimeline, function ($a, $b) {
      return $a['time'] > $b['time'];
    });

    $stateTimelinePairs = array_map(
      NULL,
      array_slice($stateTimeline, 0, -1),
      array_slice($stateTimeline, 1)
    );

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    foreach ($stateTimelinePairs as $stateTimelinePair) {
      [$from, $to] = $stateTimelinePair;

      try {
        $stateFrom = $workflow->getTypePlugin()->getState($from['state']);
        $stateTo = $workflow->getTypePlugin()->getState($to['state']);
      }
      catch (\InvalidArgumentException $e) {
        // If either state does not exist.
        continue;
      }

      $canTransition = $stateFrom->canTransitionTo($stateTo->id());
      if (!$canTransition) {
        $transitionDate = DrupalDateTime::createFromTimestamp($to['time']);
        $this->context->addViolation($constraint->messageInvalidTransition, [
          '%date' => $transitionDate->format('r'),
          '%from' => $stateFrom->label(),
          '%to' => $stateTo->label(),
        ]);
      }
    }
  }

  /**
   * Get a list of scheduled state transitions.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity which scheduled updates are associated.
   *
   * @return array
   *   An unordered array containing time and state machine name.
   */
  public function getScheduledStateTransitions(ContentEntityInterface $entity): array {
    $scheduledUpdateReferenceFields = $this->cmsuUtility
      ->getScheduledUpdateReferenceFields($entity->getEntityTypeId(), $entity->bundle());

    $timeline = [];
    foreach ($scheduledUpdateReferenceFields as $fieldName) {
      foreach ($entity->{$fieldName} as $item) {
        /** @var \Drupal\scheduled_updates\ScheduledUpdateInterface $scheduledUpdateEntity */
        $scheduledUpdateEntity = $item->entity;
        if (!$scheduledUpdateEntity instanceof ScheduledUpdateInterface) {
          continue;
        }

        // Does this scheduled update change moderation state?
        $sourceToStateFieldName = $this->cmsuUtility
          ->getModerationStateFieldName($scheduledUpdateEntity->bundle());
        if (!$sourceToStateFieldName) {
          continue;
        }

        // Does the scheduled update contain a value?
        /** @var \Drupal\Core\Field\FieldItemList $sourceToStateField */
        $sourceToStateField = $scheduledUpdateEntity->{$sourceToStateFieldName};
        if ($sourceToStateField->isEmpty()) {
          continue;
        }

        $timeline[] = [
          'time' => $scheduledUpdateEntity->update_timestamp->value ?? NULL,
          'state' => $sourceToStateField->value,
        ];
      }
    }

    return $timeline;
  }

}
