<?php

namespace Drupal\bd\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityTypeApplyUpdate.
 *
 * @BusinessRulesAction(
 *   id = "entity_type_apply_update",
 *   label = @Translation("Entity Type: Apply Updates"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Apply entity updates against entity types and field definitions."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class EntityTypeApplyUpdate extends Base {

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
    $result = \Drupal::entityDefinitionUpdateManager()->installMissingEntityType();
    return $result;
  }

}
