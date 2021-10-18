<?php

namespace Drupal\design_system\Plugin\views\display_extender;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Php\Arr;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\design_system\DesignSystem;

/**
 * Design system display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "design_system",
 *   title = @Translation("Design System"),
 *   help = @Translation("Provides design system integration with views."),
 *   no_ui = FALSE
 * )
 */
class DesignSystemDisplayExtender extends DisplayExtenderPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The view components.
   *
   * @var array
   */
  const VIEW_COMPONENT_ID = [
    'view' => 'Overall Wrapper',
    'exposed' => 'Exposed form',
    'header' => 'Header',
    'footer' => 'Footer',
    'empty' => 'Empty behavior',
    'pager' => 'Pager',
    'rows' => 'Rows',
    'attachment_before' => 'Attachment before',
    'attachment_after' => 'Attachment after',
    'feed_icons' => 'Feed icons',
  ];

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * Constructs the plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The plugin manager for metatag tags.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The metatag manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DesignSystem $design_system,
    EntityHelper $entity_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->designSystem = $design_system;
    $this->entityHelper = $entity_helper;
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
      $container->get('entity.helper')
    );
  }

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('type') != 'design_system') {
      return;
    }

    $form['design_system'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'class' => [
          'display-extender--form--design-system',
        ],
      ],
    ];

    $config = $this->options;

    foreach (static::VIEW_COMPONENT_ID as $component_id => $component_label) {
      $form['design_system'][$component_id] = [
        '#type' => 'details',
        '#title' => $this->t($component_label),
        '#open' => FALSE,
        '#tree' => TRUE,
      ];

      $form['design_system'][$component_id]['wrapper'] = $this->designSystem->buildConfigFormElementWrapper((!empty($config[$component_id]['wrapper']) ? $config[$component_id]['wrapper'] : []), 'Wrapper', TRUE, NULL, FALSE);
      $form['design_system'][$component_id]['heading'] = $this->designSystem->buildConfigFormElementHeading((!empty($config[$component_id]['heading']) ? $config[$component_id]['heading'] : []), 'Heading', FALSE, FALSE);
      $form['design_system'][$component_id]['subheading'] = $this->designSystem->buildConfigFormElementHeading((!empty($config[$component_id]['subheading']) ? $config[$component_id]['subheading'] : []), 'Subheading', FALSE, FALSE);
      $form['design_system'][$component_id]['collapse'] = $this->designSystem->buildConfigFormElementCollapse((!empty($config[$component_id]['collapse']) ? $config[$component_id]['collapse'] : []), 'Animations', FALSE, NULL, FALSE);
      $form['design_system'][$component_id]['animation'] = $this->designSystem->buildConfigFormElementAos((!empty($config[$component_id]['animation']) ? $config[$component_id]['animation'] : []), FALSE, FALSE);
    }

  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('type') != 'design_system') {
      return;
    }
  }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('type') != 'design_system') {
      return;
    }

    $config = $form_state->getValue('design_system');
    $config = Arr::removeEmpty($config);
    $this->options = $config;
  }

  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() {
  }

  /**
   * Process variables for the view element and its components around config.
   *
   * @param array $variables
   *   The view element.
   */
  public static function preprocessViewsView(array &$variables) {

    /** @var \Drupal\design_system\DesignSystem $design_system */
    $design_system = \Drupal::service('design.system');

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $variables['view'];

    $variables['attributes']['class'][] = 'view';
    $variables['attributes']['class'][] = 'ajax--wrapper';
    $variables['attributes']['data-view-id'] = $view->id();
    $variables['attributes']['data-display-id'] = $view->current_display;

    $options = isset($variables['view']->display_handler->options['display_extenders']['design_system']) ? $variables['view']->display_handler->options['display_extenders']['design_system'] : [];

    foreach (static::VIEW_COMPONENT_ID as $component_id => $label) {
      if (empty($variables["{$component_id}"])) {
        continue;
      }

      // All components get these standard attributes whether configured or
      // not.
      if ($component_id == 'view') {
        $child_element = &$element;
      }
      else {
        $child_element = &$variables["{$component_id}"];
        $child_element['#type'] = 'container';
        $child_element['#attributes']['class'][] = 'views-view--component';
        $child_element['#attributes']['class'][] = Html::cleanCssIdentifier("views-view--component--{$component_id}");
        $child_element['#attributes']['data-view-component'] = $component_id;
      }

      // Check if this compoonent is configured.
      if (empty($options[$component_id])) {
        continue;
      }

      $config = $options[$component_id];

      if (!empty($config['wrapper'])) {
        $design_system->processConfigWrapper($child_element, $config['wrapper']);

        if ($component_id == 'view' && !empty($child_element['#attributes'])) {
          $variables['attributes'] = NestedArray::mergeDeep($variables['attributes'], $child_element['#attributes']);
        }
      }

      if (!empty($config['heading']['text'])) {
        $design_system->processConfigHeading('heading', $child_element, $config['heading'], -1000);
      }

      if (!empty($config['subheading']['text'])) {
        $design_system->processConfigHeading('subheading', $child_element, $config['subheading'], -990);
      }

      if (!empty($config['collapse']['collapse'])) {
        $design_system->processConfigCollapse($child_element, $config);
      }

      if (!empty($config['animation']['enable'])) {
        $design_system->processConfigAos($child_element['#attributes'], $config);
      }

    }

  }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['design_system'] = [
      'title' => $this->t('Design System'),
      'column' => 'first',
      'weight' => -1000,
    ];
    $options['design_system'] = [
      'category' => 'design_system',
      'title' => $this->t('Design System'),
      'value' => !empty($this->options) ? $this->t('Overridden') : $this->t('Using defaults'),
    ];
  }

}
