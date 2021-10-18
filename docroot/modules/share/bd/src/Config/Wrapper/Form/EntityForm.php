<?php

namespace Drupal\bd\Config\Wrapper\Form;

use Drupal\Core\Entity\EntityForm as Base;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Config\Wrapper\Manager;
use Drupal\inline_entity_form\ElementSubmit;

/**
 * Form handler for config entity wrappers.
 */
class EntityForm extends Base {

  /**
   * The config entity wrapper manager.
   *
   * @var \Drupal\bd\Config\Wrapper\Manager
   */
  protected $configEntityWrapperManager;

  /**
   * Constructs a ConfigEntityWrapperEntityForm object.
   *
   * @param \Drupal\bd\Config\Wrapper\Manager $config_entity_wrapper_manager
   *   The config entity wrapper manager.
   */
  public function __construct(
    Manager $config_entity_wrapper_manager
  ) {
    $this->configEntityWrapperManager = $config_entity_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_entity_wrapper.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;
    $entity_type = $entity->getEntityType();

    $entity_form_op = 'default';
    $entity_type_id = $entity->getEntityTypeId();
    $entity_label = $entity->label();

    if ($entity_form_op == 'add') {
      $title = $this->t('Create %entity_type_label_singular', [
        '%entity_type_label_singular' => $entity_type->getSingularLabel(),
      ]);
    }
    else {
      $title = $this->t('Edit @entity_label', [
        '@entity_label' => $entity_label,
      ]);
    }
    $form['#title'] = $title;

    $form['config_entity_wrapper'] = [
      '#type' => 'inline_entity_form',
      '#op' => $entity_form_op,
      '#entity_type' => Manager::ENTITY_TYPE_ID_CONFIG_WRAPPER,
      '#bundle' => $entity_type_id,
      '#form_mode' => $entity_form_op,
      '#default_value' => $this->configEntityWrapperManager->getWrapperForEntity($entity),
      '#save_entity' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    ElementSubmit::doSubmit($form['config_entity_wrapper'], $form_state);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_wrapper */
    $entity_wrapper = $form['config_entity_wrapper']['#entity'];
    $entity_subject = $this->entity;

    // @todo bug where new values to existing entities not saved in IEF.
    $entity_wrapper->save();

    $this->entity = $this->configEntityWrapperManager->syncWrapperToEntity($entity_wrapper, $entity_subject, FALSE);

    return parent::save($form, $form_state);
  }

}
