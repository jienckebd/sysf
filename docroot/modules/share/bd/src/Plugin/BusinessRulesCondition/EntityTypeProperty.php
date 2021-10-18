<?php

namespace Drupal\bd\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityTypeProperty.
 *
 * @BusinessRulesCondition(
 *   id = "entity_type_property",
 *   label = @Translation("Entity Type: Property"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Compare a property of the entity type."),
 *   isContextDependent = TRUE,
 *   reactsOnIds = {},
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class EntityTypeProperty extends Base {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $condition) {
    // Only show settings form if the item is already saved.
    if ($condition->isNew()) {
      return [];
    }
    $settings = [];
    return $settings;
  }

  /**
   * Performs the form validation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    return TRUE;
  }

}
