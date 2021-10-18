<?php

namespace Drupal\bd\Config\Wrapper\Form;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Config\Wrapper\Manager;

/**
 * Extends ECK entity bundle form for config_entity_wrapper_type entity type.
 */
class Bundle extends BundleEntityFormBase {

  /**
   * The config entity wrapper manager.
   *
   * @var \Drupal\bd\Config\Wrapper\Manager
   */
  protected $configEntityWrapperManager;

  /**
   * @param \Drupal\bd\Entity\EntityHelper $entityHelper
   */
  public function __construct(
    EntityHelper $entityHelper,
    Manager $config_entity_wrapper_manager
  ) {
    $this->entityHelper = $entityHelper;
    $this->configEntityWrapperManager = $config_entity_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.helper'),
      $container->get('config_entity_wrapper.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['third_party_settings']['bd'] = [
      '#type' => 'details',
      '#title' => t('Config Entity Wrapper'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['third_party_settings']['bd']['config_schema_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Config Schema'),
      '#options' => $this->configEntityWrapperManager->getOptionTypedConfig(),
      '#default_value' => $this->entity->getThirdPartySetting('bd', 'config_schema_type'),
      '#required' => TRUE,
    ];

    $form['third_party_settings']['bd']['entity_type_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity Type ID'),
      '#default_value' => $this->entity->getThirdPartySetting('bd', 'entity_type_id'),
    ];

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->set('id', $form_state->getValue(['config_entity_wrapper', 'entity_type_id']));
    $this->entity->set('config_schema_type', $form_state->getValue(['config_entity_wrapper', 'config_schema_type']));
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->setThirdPartySetting('config_entity_wrapper', 'config_schema_type', $form_state->getValue(['config_entity_wrapper', 'config_schema_type']));
    $this->entity->setThirdPartySetting('config_entity_wrapper', 'entity_type_id', $form_state->getValue(['config_entity_wrapper', 'entity_type_id']));
    return parent::save($form, $form_state);
  }

}
