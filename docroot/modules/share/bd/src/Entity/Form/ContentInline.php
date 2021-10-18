<?php

namespace Drupal\bd\Entity\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Content entity inline form handler.
 */
class ContentInline extends EntityInlineForm {
  use InlineTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function entityFormValidate(array &$entity_form, FormStateInterface $form_state) {

    // This needs to run first to ensure entity is built.
    parent::entityFormValidate($entity_form, $form_state);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_form['#entity'];

    $save = TRUE;
    $required_field = ['dom'];
    foreach ($required_field as $field_name) {

      if (!$entity->hasField($field_name)) {
        $save = FALSE;
        break;
      }

      if ($entity->get($field_name)->isEmpty()) {
        $save = FALSE;
        break;
      }

    }

    if (!$save) {
      $entity_form['#save_entity'] = FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function entityFormSubmit(array &$entity_form, FormStateInterface $form_state) {
    parent::entityFormSubmit($entity_form, $form_state);
  }

}
