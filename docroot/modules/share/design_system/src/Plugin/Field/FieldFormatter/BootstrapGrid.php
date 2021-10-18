<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\design_system\DesignSystem;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceFormatterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference_grid' formatter.
 *
 * @FieldFormatter(
 *   id = "bootstrap_grid",
 *   label = @Translation("Bootstrap grid"),
 *   field_types = {
 *     "entity_reference",
 *     "dynamic_entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class BootstrapGrid extends Base {

  use DynamicEntityReferenceTrait;
  use DynamicEntityReferenceFormatterTrait {
    prepareView as dynamicEntityReferencePrepareView;
  }

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * Constructs a BootstrapGrid instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityHelper $entity_helper, EntityDisplayRepositoryInterface $entity_display_repository, DesignSystem $design_system) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_helper, $entity_display_repository);
    $this->designSystem = $design_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity.helper'),
      $container->get('entity_display.repository'),
      $container->get('design.system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'class_container' => [
        'container',
      ],
      'class_row' => [
        'row',
        'row--gutter--md',
        'justify-content-center',
      ],
      'class_col' => [
        'col-xs-24',
        'col-lg-8',
      ],
    ] + parent::defaultSettings();

    $settings += static::getDynamicEntityReferenceDefaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    unset($element['view_mode']);

    $element['class_container'] = [
      '#type' => 'select',
      '#title' => $this->t('Container classes'),
      '#default_value' => $this->getSetting('class_container'),
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    $element['class_row'] = [
      '#type' => 'select',
      '#title' => $this->t('Row classes'),
      '#default_value' => $this->getSetting('class_row'),
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    $element['class_col'] = [
      '#type' => 'select',
      '#title' => $this->t('Column classes'),
      '#default_value' => $this->getSetting('class_col'),
      '#options' => $this->designSystem->getOption('class.wrapper'),
      '#multiple' => TRUE,
      '#normalize' => TRUE,
    ];

    $element += $this->getDynamicEntityReferenceViewModeElement();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {

    if (!empty($entities_items[0]) && ($entities_items[0] instanceof DynamicEntityReferenceFieldItemList)) {
      return $this->dynamicEntityReferencePrepareView($entities_items);
    }

    return parent::prepareView($entities_items);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);
    if (empty($elements)) {
      return [];
    }

    $build = [
      '#theme' => 'container',
      '#attributes' => [
        'class' => $this->getSetting('class_container'),
      ],
    ];

    $build['#children']['items']['#theme'] = 'container';
    $build['#children']['items']['#attributes']['class'] = $this->getSetting('class_row');

    $settings = $this->getSettings();

    foreach ($elements as $delta => &$child) {

      $field_item = $items->get($delta);
      $entity_type_id = $field_item->entity->getEntityTypeId();

      $child['#view_mode'] = isset($settings[$entity_type_id]['view_mode']) ? $settings[$entity_type_id]['view_mode'] : 'default';

      $build['#children']['items']['#children'][$delta] = [
        '#theme' => 'container',
        '#attributes' => [
          'class' => $this->getSetting('class_col'),
        ],
        '#children' => $child,
      ];
    }

    return $build;
  }

}
