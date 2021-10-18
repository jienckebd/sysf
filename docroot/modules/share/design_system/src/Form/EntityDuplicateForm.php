<?php

namespace Drupal\design_system\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;

/**
 *
 */
class EntityDuplicateForm extends ContentEntityConfirmFormBase {

  use EntityDuplicateFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->sourceEntity->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clone');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clone @entity_type_label_singular @entity_label', [
      '@entity_type_label_singular' => $this->entity->getEntityType()->getLabel(),
      '@entity_label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $new_label_initial = $this->t('@entity_label cloned', [
      '@entity_label' => $this->entity->label(),
    ])->__toString();

    $entity_type = $this->entity->getEntityType();
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $form['new_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New label'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['new_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $new_label_initial,
        '#required' => TRUE,
        '#weight' => -30,
      ];
      $form['id'] = [
        '#type' => 'machine_name',
        '#required' => TRUE,
        '#default_value' => NULL,
        '#maxlength' => 255,
        '#machine_name' => [
          'exists' => [$this, 'exists'],
          'source' => ['new_label'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity_type = $this->entity->getEntityType();

    $label_key = $entity_type->getKey('label');
    $id_key = $entity_type->getKey('id');

    if ($entity_type instanceof ContentEntityTypeInterface) {
      $this->entity->set($label_key, $form_state->getValue('new_label'));
      $this->entity->setValidationRequired(FALSE);
    }
    else {
      $this->entity->set($label_key, $form_state->getValue('new_label'));
      $this->entity->set($id_key, $form_state->getValue('id'));
    }

    $this->save($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);

    $this->messenger()->addMessage($this->t('Successfully cloned @entity_type_label_singular @entity_label.', [
      '@entity_type_label_singular' => $this->entity->getEntityType()->getLabel(),
      '@entity_label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl());
  }

}
