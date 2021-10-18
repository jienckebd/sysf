<?php

namespace Drupal\design_system\Form\Inline;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm as Base;

/**
 * Inline form handler for redirect entity.
 */
class Redirect extends Base {

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {

    /** @var \Drupal\Core\Entity\EntityInterface $entity_parent */
    $entity_parent = $form_state->getFormObject()->getEntity();
    if (!$entity_parent->id()) {
      \Drupal::messenger()->addError(t('This form can only be used on existing content.'));
      return $entity_form;
    }

    /** @var \Drupal\redirect\Entity\Redirect $entity */
    $entity = $entity_form['#entity'];

    // Set a temporary value. It'll be updated after form save.
    $entity->set('redirect_redirect', '/');
    $entity_form = parent::entityForm($entity_form, $form_state);

    // Hide the redirect target element.
    $entity_form['redirect_redirect']['#access'] = FALSE;

    return $entity_form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $entity->save();
  }

}
