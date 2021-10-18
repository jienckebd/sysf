<?php

namespace Drupal\bd\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DeleteRelation.
 *
 * @BusinessRulesAction(
 *   id = "delete_dependent",
 *   label = @Translation("Delete Relations"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Deletes relations based on field config."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class DeleteRelation extends Base {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings = [];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_context */
    $entity_context = $event->getSubject();

    /** @var \Drupal\bd\Entity\EntityRelation $entity_relation */
    $entity_relation = \Drupal::service('entity.relation');
    $entity_relation->deleteDependencyForEntity($entity_context);

  }

}
