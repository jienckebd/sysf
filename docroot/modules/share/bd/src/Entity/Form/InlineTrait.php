<?php

namespace Drupal\bd\Entity\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait InlineTrait.
 *
 * @package Druipal\bd\Entity\Form
 */
trait InlineTrait {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    return $entity_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    return [
      'singular' => $this->entityType->getSingularLabel(),
      'plural' => $this->entityType->getPluralLabel(),
    ];
  }

}
