<?php

namespace Drupal\bd\Entity\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for generic entity bundle form.
 *
 * @internal
 */
class Bundle extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;

    $entity_type = $this->entity->getEntityType();

    $entity_id_key = $entity_type->getKey('id');
    $entity_label_key = $entity_type->getKey('label');
    $entity_type_label = $entity_type->getLabel();

    $form[$entity_label_key] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity->get($entity_label_key),
      '#description' => t(
        'The human-readable name of this entity bundle. This text will be displayed as part of the list on the <em>Add @type content</em> page. This name must be unique.',
        ['@type' => $entity_type_label]),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form[$entity_id_key] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => [$entity_label_key],
      ],
      '#description' => t(
        'A unique machine-readable name for this entity type bundle. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the Add %type content page, in which underscores will be converted into hyphens.',
        [
          '%type' => $entity_type_label,
        ]
      ),
    ];

    if (method_exists($entity, 'isLocked')) {
      $form['type']['#disabled'] = $entity->isLocked();
    }

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $entity->get('description'),
      '#description' => t(
        'Describe this entity type bundle. The text will be displayed on the <em>Add @type content</em> page.',
        ['@type' => $entity_type_label]
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save bundle');
    $actions['delete']['#value'] = t('Delete bundle');

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName(
        'type',
        $this->t(
          "Invalid machine-readable name. Enter a name other than %invalid.",
          ['%invalid' => $id]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    $t_args = ['%name' => $entity->label()];

    if ($status == SAVED_UPDATED) {
      \Drupal::messenger()->addMessage($this->t('The entity bundle %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      \Drupal::messenger()->addMessage($this->t('The entity bundle %name has been added.', $t_args));
    }
  }

  /**
   * Checks for an existing ECK bundle.
   *
   * @param string $entity
   *   The bundle type.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this bundle already exists in the entity type, FALSE otherwise.
   */
  public function exists($entity, array $element, FormStateInterface $form_state) {
    $bundleStorage = \Drupal::service('entity.helper')->getStorage($this->entity->getEntityTypeId());
    return (bool) $bundleStorage->load($entity);
  }

}
