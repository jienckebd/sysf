<?php

namespace Drupal\autoref\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Entity\EntityHelper;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
class Settings extends ConfigFormBase {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityHelper $entity_helper, RequestContext $request_context) {
    parent::__construct($config_factory);
    $this->entityHelper = $entity_helper;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.helper'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autoref_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['autoref.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('autoref.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => t('General Settings'),
      '#open' => TRUE,
    ];
    $form['general']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate'),
      '#default_value' => $settings->get('status'),
      '#description' => $this->t('Activate or deactivate auto referencing.'),
    ];

    $form['entity'] = [
      '#type' => 'details',
      '#title' => t('Entity API'),
      '#open' => TRUE,
    ];

    $options_entity_type = [];
    foreach ($this->entityHelper->getDefinitions() as $key => $entity_type) {
      $options_entity_type[$entity_type->id()] = $entity_type->getLabel() . ' (' . $entity_type->id() . ')';
    }

    $form['entity']['entity_type'] = [
      '#type' => 'checkboxes',
      '#title' => t('Entity Types'),
      '#options' => $options_entity_type,
      '#default_value' => $settings->get('entity_type') ?: [],
      '#description' => $this->t('Configure the entity types that will have their references auto set.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('autoref.settings')
      ->set('status', $form_state->getValue('status'))
      ->set('entity_type', $form_state->getValue('entity_type'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
