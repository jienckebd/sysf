<?php

namespace Drupal\design_system\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\bd\Entity\EntityHelper;
use Drupal\design_system\DesignSystem;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\design_system\Component\FormIntegrationTrait;

/**
 * Views button area handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("component")
 */
class Component extends AreaPluginBase {

  use FormIntegrationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * Constructs a View object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityHelper $entity_helper,
    DesignSystem $design_system
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityHelper = $entity_helper;
    $this->designSystem = $design_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.helper'),
      $container->get('design.system')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['component_wrapper'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['component_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Component'),
      '#open' => TRUE,
    ];
    $this->attachSettingsForm($form['component_wrapper'], $form_state, $this->options['component_wrapper']);

  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $element = [];

    if (empty($this->options['component_wrapper']['component'])) {
      return $element;
    }

    return $this->designSystem->viewComponent($this->options['component_wrapper']['component']);
  }

}
