<?php

namespace Drupal\bd\Plugin;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a base implementation for a configurable entity plugins.
 */
abstract class EntityPluginBase extends ContextAwarePluginBase implements ConfigurableInterface, DependentPluginInterface, PluginFormInterface, EntityPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The config schema ID.
   *
   * @var string
   */
  protected $configSchemaId;

  /**
   * The config schema.
   *
   * @var array|null
   */
  protected $configSchema;

  /**
   * EntityPluginBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityHelper $entity_helper,
    TypedConfigManagerInterface $typed_config_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityHelper = $entity_helper;
    $this->typedConfigManager = $typed_config_manager;
    $this->setConfiguration($configuration);

    $plugin_type = $plugin_definition['plugin_type'];
    $plugin_id = $this->pluginId;

    $this->configSchemaId = "plugin.plugin_configuration.{$plugin_type}.{$plugin_id}";
    if (!$this->configSchema = $this->typedConfigManager->getDefinition($this->configSchemaId)) {
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.helper'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    static::recurseRemove($values);
    $form_state->setValues($values);

  }

  /**
   *
   */
  protected function recurseRemove(array &$values) {

    foreach ($values as $key => &$value) {

      if (in_array($key, ['widget', 'value', 'target_id'])) {
        $values = $value;
      }

      if (is_array($value)) {
        static::recurseRemove($value);
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $settings = $this->configuration;

    $element = [
      '#type' => 'config_schema_subform',
      '#config_schema_id' => $this->configSchemaId,
      '#config_data' => $settings,
      '#is_new' => empty($settings),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

}
