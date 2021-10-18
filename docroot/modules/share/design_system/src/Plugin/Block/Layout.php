<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\design_system\DesignSystem;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;

/**
 * Provides a block that renders a layout.
 *
 * @Block(
 *   id = "layout",
 *   category = @Translation("Layout"),
 *   deriver = "\Drupal\design_system\Plugin\Derivative\StandardLayoutDeriver",
 * )
 */
class Layout extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * Layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new Layout block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_reposiitory
   *   The layout builder temp store.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DesignSystem $design_system,
    LayoutPluginManagerInterface $layout_plugin_manager,
    LayoutTempstoreRepositoryInterface $layout_tempstore_reposiitory,
    RouteMatchInterface $route_match,
    LoggerInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->designSystem = $design_system;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->layoutTempstoreRepository = $layout_tempstore_reposiitory;
    $this->routeMatch = $route_match;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('design.system'),
      $container->get('plugin.manager.core.layout'),
      $container->get('layout_builder.tempstore_repository'),
      $container->get('current_route_match'),
      $container->get('logger.channel.design_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'layout_settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $layout = $this->getLayout($this->configuration['layout_settings']);

    $form['layout_settings'] = [];
    $subform_state = SubformState::createForSubform($form['layout_settings'], $form, $form_state);
    $form['layout_settings'] = $layout->buildConfigurationForm($form['layout_settings'], $subform_state);

    $form['#process'][] = [$this, 'processComponentBlockForm'];
    $form['#after_build'][] = [$this, 'afterBuildComponentBlockForm'];

    return $form;
  }

  /**
   * @param array $layout_settings
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getLayout($layout_settings = []) {
    $layout_plugin_id = $this->getPluginDefinition()['layout_plugin_id'];
    $layout = $this->layoutPluginManager->createInstance($layout_plugin_id, $layout_settings);
    return $layout;
  }

  /**
   * Process callback for component block form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array
   *   The processed form.
   */
  public function processComponentBlockForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    // Hide the default block form and use component entity fields for heading.
    $element['admin_label']['#access'] = FALSE;
    $element['label']['#access'] = FALSE;
    $element['label_display']['#access'] = FALSE;
    $element['label_display']['#default_value'] = FALSE;
    return $element;
  }

  /**
   * After build callback for component block form.
   *
   * @param array $element
   *   The form element.
   *
   * @return array
   *   The processed form.
   */
  public function afterBuildComponentBlockForm(array $element) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    // Save the layout config to the block config.
    $layout = $this->getLayout($this->configuration['layout_settings']);
    $subform_state = SubformState::createForSubform($form['settings']['layout_settings'], $form['settings'], $form_state);
    $layout->submitConfigurationForm($form['settings']['layout_settings'], $subform_state);
    $this->configuration['layout_settings'] = $layout->getConfiguration();

    // Update dynamic regions config of parent layout.
    $route_parameters = $this->routeMatch->getParameters();

    /** @var \Drupal\layout_builder\SectionStorageInterface $section_storage */
    $section_storage = $route_parameters->get('section_storage');
    $parent_layout_delta = $route_parameters->get('delta');
    $parent_layout_region = $route_parameters->get('region');
    $parent_layout_section = $section_storage->getSection($parent_layout_delta);
    $parent_layout = $parent_layout_section->getLayout();
    $parent_layout_config = $parent_layout->getConfiguration();

    /** @var \Drupal\layout_builder\SectionComponent $layout_builder_component */
    $layout_builder_component = $form_state->getFormObject()->getCurrentComponent();

    $layout_builder_component_block_uuid = $layout_builder_component->getUuid();

    foreach ($layout->getPluginDefinition()->getRegions() as $sublayout_region_id => $sublayout_region_config) {

      $sublayout_region_label = $this->t('@parent_region_id: Sublayout: @child_region_id', [
        '@parent_region_id' => $parent_layout_region,
        '@child_region_id' => $sublayout_region_id,
      ]);

      $parent_layout_config['region'][$parent_layout_region]['sublayout'][$layout_builder_component_block_uuid]['region'][$sublayout_region_id] = [
        'label' => $sublayout_region_label,
      ];
    }

    $parent_layout_section->setLayoutSettings($parent_layout_config);
    $this->layoutTempstoreRepository->set($section_storage);

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#sublayout_configuration'] = $this->configuration;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    return new TranslatableMarkup('Sublayout @id', [
      '@id' => $this->getPluginDefinition()['layout_plugin_id'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [];
    return $cache_contexts;
  }

}
