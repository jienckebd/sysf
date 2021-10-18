<?php

namespace Drupal\bd\Plugin\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Action\ConfigurableActionBase;

/**
 * Provides an action that can save any entity.
 *
 * @Action(
 *   id = "entity:save_action:config",
 *   action_label = @Translation("Resave"),
 *   deriver = "\Drupal\bd\Plugin\Action\Derivative\ContentEntityActionDeriver",
 * )
 */
class EntitySave extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'create_new_revision' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setChangedTime(REQUEST_TIME);

    if (!empty($this->configuration['create_new_revision'])) {
      $entity->setNewRevision(TRUE);
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // It's not necessary to check the changed field access here, because
    // Drupal\Core\Field\ChangedFieldItemList would anyway return 'not allowed'.
    // Also changing the changed field value is only a workaround to trigger an
    // entity resave. Without a field change, this would not be possible.
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['create_new_revision'] = [
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => isset($this->configuration['create_new_revision']) ? $this->configuration['create_new_revision'] : FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['create_new_revision'] = $form_state->getValue('create_new_revision');
  }

}
