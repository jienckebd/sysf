<?php

namespace Drupal\bd\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for business rules condition plugins.
 */
abstract class Base extends BusinessRulesConditionPlugin {

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
