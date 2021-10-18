<?php

namespace Drupal\bd\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityTypeUninstallMissing.
 *
 * @BusinessRulesAction(
 *   id = "entity_type_uninstall_missing",
 *   label = @Translation("Entity Type: Uninstall missing"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Uninstall entity types removed from system."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class EntityTypeUninstallMissing extends Base {

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
    $result = \Drupal::entityDefinitionUpdateManager()->uninstallMissingEntityType();
    return $result;
  }

}
