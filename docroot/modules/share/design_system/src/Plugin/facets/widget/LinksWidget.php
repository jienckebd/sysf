<?php

namespace Drupal\design_system\Plugin\facets\widget;

use Drupal\design_system\DesignSystem;
use Drupal\facets\Plugin\facets\widget\LinksWidget as Base;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "links",
 *   label = @Translation("List of links"),
 *   description = @Translation("A simple widget that shows a list of links"),
 * )
 */
class LinksWidget extends Base implements ContainerFactoryPluginInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Request $request,
    DesignSystem $design_system
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
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
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('design.system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ul_class' => [],
      'li_class' => [],
      'li_active_class' => [],
      'li_inactive_class' => [],
      'a_class' => [],
      'a_active_class' => [],
      'a_inactive_class' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $form['ul_class'] = [
      '#type' => 'select',
      '#normalize' => TRUE,
      '#title' => $this->t('ul classes'),
      '#default_value' => $this->configuration['ul_class'],
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
    ];

    $form['li_class'] = [
      '#type' => 'select',
      '#normalize' => TRUE,
      '#title' => $this->t('li classes'),
      '#default_value' => $this->configuration['li_class'],
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
    ];

    $form['li_active_class'] = [
      '#type' => 'select',
      '#title' => $this->t('li active classes'),
      '#default_value' => $this->configuration['li_active_class'],
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    $form['li_inactive_class'] = [
      '#type' => 'select',
      '#title' => $this->t('li inactive classes'),
      '#default_value' => $this->configuration['li_inactive_class'],
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    $form['a_class'] = [
      '#type' => 'select',
      '#title' => $this->t('a classes'),
      '#default_value' => $this->configuration['a_class'],
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    $form['a_active_class'] = [
      '#type' => 'select',
      '#title' => $this->t('a active classes'),
      '#default_value' => $this->configuration['a_active_class'],
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    $form['a_inactive_class'] = [
      '#type' => 'select',
      '#title' => $this->t('a inactive classes'),
      '#default_value' => $this->configuration['a_inactive_class'],
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);
    if (empty($build['#items'])) {
      return $build;
    }

    $results = $facet->getResults();

    if (!empty($this->configuration['ul_class'])) {
      foreach ($this->configuration['ul_class'] as $class) {
        $build['#attributes']['class'][] = $class;
      }
    }

    foreach ($build['#items'] as $key => &$child) {

      $result = $results[$key];

      if (!empty($this->configuration['li_class'])) {
        foreach ($this->configuration['li_class'] as $class) {
          $child['#wrapper_attributes']['class'][] = $class;
        }
      }

      if (!empty($this->configuration['a_class'])) {
        foreach ($this->configuration['a_class'] as $class) {
          $child['#attributes']['class'][] = $class;
        }
      }

      if ($result->isActive()) {

        if (!empty($this->configuration['li_active_class'])) {
          foreach ($this->configuration['li_active_class'] as $class) {
            $child['#wrapper_attributes']['class'][] = $class;
          }
        }

        if (!empty($this->configuration['a_active_class'])) {
          foreach ($this->configuration['a_active_class'] as $class) {
            $child['#attributes']['class'][] = $class;
          }
        }

      }
      else {

        if (!empty($this->configuration['li_inactive_class'])) {
          foreach ($this->configuration['li_inactive_class'] as $class) {
            $child['#wrapper_attributes']['class'][] = $class;
          }
        }

        if (!empty($this->configuration['a_inactive_class'])) {
          foreach ($this->configuration['a_inactive_class'] as $class) {
            $child['#attributes']['class'][] = $class;
          }
        }

      }

    }

    return $build;
  }

}
