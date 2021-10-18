<?php

namespace Drupal\design_system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\design_system\DesignSystem;

/**
 * Class DesignSystemSettings.
 */
class DesignSystemSettings extends ConfigFormBase {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module hzandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\aggregator\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key.
   * @param \Drupal\acquia_connector\Client $client
   *   The Acquia client.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['design_system.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'design_system_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(DesignSystem::CONFIG_ID);

    $form['color'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['color']['scheme'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Color Scheme'),
      '#default_value' => $config->get('color.scheme'),
    ];

    $form['color']['style'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Styles'),
      '#default_value' => $config->get('color.style'),
    ];

    $form['class'] = [
      '#type' => 'details',
      '#title' => $this->t('Classes'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['class']['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text Classes'),
      '#default_value' => $config->get('class.text'),
    ];

    $form['class']['wrapper'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Wrapper Classes'),
      '#default_value' => $config->get('class.wrapper'),
    ];

    $form['tag'] = [
      '#type' => 'details',
      '#title' => $this->t('Tags'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['tag']['wrapper'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Wrapper Tags'),
      '#default_value' => $config->get('tag.wrapper'),
    ];

    $form['tag']['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text Tags'),
      '#default_value' => $config->get('tag.text'),
    ];

    $form['space'] = [
      '#type' => 'details',
      '#title' => $this->t('Space'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['space']['container'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Container Options'),
      '#default_value' => $config->get('space.container'),
    ];

    $form['button'] = [
      '#type' => 'details',
      '#title' => $this->t('Buttons'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['button']['type'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Button Type Classes'),
      '#default_value' => $config->get('button.type'),
    ];

    $form['button']['size'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Button Size Classes'),
      '#default_value' => $config->get('button.size'),
    ];

    $form['button']['icon_position'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Icon Position'),
      '#default_value' => $config->get('button.icon_position'),
    ];

    $form['attribute'] = [
      '#type' => 'details',
      '#title' => $this->t('Attributes'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['attribute']['entity_field_name'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Entity Fields'),
      '#default_value' => $config->get('attribute.entity_field_name'),
      '#description' => $this->t('If an entity has these fields, their values will be attached to the wrapper in the format of data-{{ field_name }} without the field_ prefix.'),
    ];

    $form['link'] = [
      '#type' => 'details',
      '#title' => $this->t('Linking'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['link']['type'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Link Types'),
      '#default_value' => $config->get('link.type'),
      '#description' => $this->t('Provide ajax modal, off canvas, tooltip, etc. link types.'),
    ];

    $form['link']['toggle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Toggle Types'),
      '#default_value' => $config->get('link.toggle'),
      '#description' => $this->t('Provide toggle types.'),
    ];

    $form['link']['tooltip'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tooltip Types'),
      '#default_value' => $config->get('link.tooltip'),
      '#description' => $this->t('Provide tooltip types.'),
    ];

    $form['media'] = [
      '#type' => 'details',
      '#title' => $this->t('Media'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['media']['image_style_standard'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Standard Image Styles'),
      '#default_value' => $config->get('media.image_style_standard'),
      '#description' => $this->t('Authors will only see these image styles when building content.'),
    ];

    $form['alert'] = [
      '#type' => 'details',
      '#title' => $this->t('Alerts'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['alert']['type'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Alert types'),
      '#default_value' => $config->get('alert.type'),
      '#description' => $this->t('Specify alert types.'),
    ];

    $form['alert']['safe_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Safe Text'),
      '#default_value' => $config->get('alert.safe_text'),
      '#description' => $this->t('Messages with this text will not autohide by default.'),
    ];

    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Forms'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['form']['element_default'] = [
      '#type' => 'details',
      '#title' => $this->t('Element Defaults'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['form']['element_default']['title_display'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Title Display'),
      '#default_value' => $config->get('form.element_default.title_display'),
      '#description' => $this->t('Decide how a form label displays by default.'),
    ];

    $form['form']['element_default']['description_display'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description Display'),
      '#default_value' => $config->get('form.element_default.description_display'),
      '#description' => $this->t('Decide how form descriptions display by default.'),
    ];

    $form['icon'] = [
      '#type' => 'details',
      '#title' => $this->t('Icons'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['icon']['entity_type'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Entity types and bundles'),
      '#default_value' => $config->get('icon.entity_type'),
      '#description' => $this->t('Enter entity type and bundle to icon mapping with * as wildcard. For example, enter "node.*" or "node.article".'),
    ];

    $form['icon']['button_label'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Button Label'),
      '#default_value' => $config->get('icon.button_label'),
      '#description' => $this->t('Enter button label to icon mapping with * as wildcard.'),
    ];

    $form['toolbar'] = [
      '#type' => 'details',
      '#title' => $this->t('Icons'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['toolbar']['tab_icon'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tab icons'),
      '#default_value' => $config->get('toolbar.tab_icon'),
      '#description' => $this->t('Enter tab name to icon mapping.'),
    ];

    $form['entity'] = [
      '#type' => 'details',
      '#title' => $this->t('Entities'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['entity']['no_theme'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Entity types to not theme'),
      '#default_value' => $config->get('entity.no_theme'),
    ];

    $form['entity_field'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity fields'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['entity_field']['empty_image_default'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Empty image default'),
      '#default_value' => $config->get('entity_field.empty_image_default'),
    ];

    $form['entity_field']['empty_text_default'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Empty text default'),
      '#default_value' => $config->get('entity_field.empty_text_default'),
    ];

    $form['option_set'] = [
      '#type' => 'details',
      '#title' => $this->t('Option sets'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['option_set']['align'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Align'),
      '#default_value' => $config->get('option_set.align'),
    ];

    $form['option_set']['justify'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Justify'),
      '#default_value' => $config->get('option_set.justify'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config(DesignSystem::CONFIG_ID);
    $values = $form_state->getValues();
    $config->setData($values);
    $config->save();

    parent::submitForm($form, $form_state);

  }

}
