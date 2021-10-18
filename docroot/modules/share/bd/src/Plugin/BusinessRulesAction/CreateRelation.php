<?php

namespace Drupal\bd\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class CreateRelation.
 *
 * @BusinessRulesAction(
 *   id = "create_relation",
 *   label = @Translation("Create Relation"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Create relations based on field config."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CreateRelation extends Base {

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

    $entity_context = $event->getSubject();

    if (!$entity_context instanceof ContentEntityInterface) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_context */

    /** @var \Drupal\bd\Entity\EntityRelation $entity_relation */
    $entity_relation = \Drupal::service('entity.relation');
    $entity_relation->createDependencyForEntity($entity_context);

  }

}
