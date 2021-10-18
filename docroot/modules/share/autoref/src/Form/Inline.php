<?php

namespace Drupal\autoref\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for order items.
 */
class Inline extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => t('autoref'),
      'plural' => t('autoreferences'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);

    if (!empty($fields['label'])) {
      unset($fields['label']);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);

    return $entity_form;
  }

}
