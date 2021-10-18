<?php

namespace Drupal\design_system\Plugin\Layout;

use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\design_system\DesignSystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Generic class for dynamic entity layout.
 */
class LayoutEntity extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderLayout'];
  }

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity layout.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $layoutEntity;

  /**
   * Whether or not this is in preview.
   *
   * @var bool
   */
  protected $isPreview;

  /**
   * Constructs a ComponentLayoutBundle object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id for the layout.
   * @param mixed $layout_definition
   *   The plugin implementation definition.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $layout_definition,
    EntityHelper $entity_helper,
    EntityDisplayRepositoryInterface $entity_display_repository,
    LayoutPluginManagerInterface $layout_plugin_manager,
    DesignSystem $design_system,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $layout_definition);
    $this->entityHelper = $entity_helper;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->designSystem = $design_system;
    $this->routeMatch = $route_match;
    $route_object = $this->routeMatch->getRouteObject();
    if (!empty($route_object)) {
      $this->isPreview = $route_object->hasDefault('section_storage_type');
    }
    else {
      $this->isPreview = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $layout_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $layout_definition,
      $container->get('entity.helper'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.core.layout'),
      $container->get('design.system'),
      $container->get('current_route_match')
    );
  }

  /**
   * @param false $clone
   *
   * @return \Drupal\block_content\BlockContentInterface|\Drupal\Core\Entity\EntityInterface|false
   */
  public function getLayoutEntity($clone = FALSE) {
    if (isset($this->layoutEntity)) {
      return $this->layoutEntity;
    }

    if (!empty($this->configuration['layout_entity'])) {
      $this->layoutEntity = $this->designSystem->getComponent($this->configuration['layout_entity']);
    }
    else {
      $plugin_definition_additional = $this->pluginDefinition->get('additional');
      $layout_revision_id = $plugin_definition_additional['revision_id'];
      $this->layoutEntity = $this->designSystem->getComponent($layout_revision_id);
    }

    return $this->layoutEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    // Prevent default configuration from setting for existing layouts.
    if (!empty($this->configuration['uuid'])) {
      return [];
    }

    $config['layout_entity'] = NULL;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['layout_entity'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => DesignSystem::ENTITY_TYPE_ID_COMPONENT,
      '#bundle' => 'layout',
      '#form_mode' => 'default',
    ];

    $additional = $this->pluginDefinition->get('additional');

    if (!empty($this->configuration['layout_entity'])) {
      $entity_layout = $this->designSystem->getComponent($this->configuration['layout_entity']);
    }
    elseif (!empty($additional['entity_id'])) {
      // This is a new cloned layout. Create duplicate.
      $entity_layout_base = $this->designSystem->getComponent($additional['entity_id']);
      $entity_layout = $entity_layout_base->createDuplicate();

      /** @var \Drupal\bd\Entity\EntityBuilder $entity_builder */
      $entity_builder = \Drupal::service('entity.builder');

      // $entity_builder->fromEntity($duplicate);
      $entity_builder->cloneReferences($entity_layout, ['block_content', 'dom']);

      $entity_layout->set('tags', []);
      $entity_layout->set('changed', \Drupal::time()->getCurrentTime());
      $form['#layout_entity_clone_base'] = $entity_layout_base;
    }

    $form['layout_entity']['#default_value'] = $entity_layout ?? NULL;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $entity_layout = $form['layout_entity']['#entity'];
    $this->configuration['layout_entity'] = $entity_layout->getRevisionId();
    $this->configuration['label'] = $entity_layout->label();
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {

    $build = [];

    if (!$layout_entity = $this->getLayoutEntity()) {
      // New layout.
      return $build;
    }

    $mapped_regions = parent::build($regions);

    $build = $this->designSystem->viewComponent($layout_entity);

    $layout_plugin_definition = $this->getPluginDefinition();

    $build['#layout'] = $layout_plugin_definition;
    $build['#attributes']['class'][] = 'layout';

    $layout_config = $this->designSystem->getLayoutEntityConfig($layout_entity);

    $build['#pre_render'][] = [$this, 'preRenderLayout'];

    foreach ($layout_config['row'] as $row_id => $row_config) {
      foreach ($row_config['region'] as $region_id => $region_config) {

        $revision_id_region = $region_config['revision_id'];

        $build[$region_id] = $this->designSystem->viewComponent($revision_id_region);
        $build[$region_id]['#attributes']['data-region'] = $region_id;
        $build[$region_id]['#attributes']['class'][] = 'region';

        if (!empty($mapped_regions[$region_id])) {
          foreach ($mapped_regions[$region_id] as $uuid => $build_layout_builder_component) {

            if (!$this->isPreview && isset($build_layout_builder_component['content']) && !empty($build_layout_builder_component['#theme']) && ($build_layout_builder_component['#theme'] == 'block')) {
              $build[$region_id][$uuid] = $build_layout_builder_component;

            }
            else {
              $build[$region_id][$uuid] = $build_layout_builder_component;
            }

          }
        }

      }
    }

    return $build;
  }

  /**
   * @param array $element
   *
   * @return array
   */
  public function preRenderLayout(array $element) {

    foreach (Element::children($element) as $child_key) {

      $child = &$element[$child_key];
      if (!empty($child['build']['#block_content'])) {

        /** @var \Drupal\block_content\BlockContentInterface $entity_block_content */
        $entity_block_content = $child['build']['#block_content'];

        if ($entity_block_content->bundle() == 'region') {
          unset($child['build']);
        }

      }

    }

    return $element;
  }

}
