<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "form",
 *   label = @Translation("Form"),
 *   field_types = {
 *     "entity_reference",
 *     "list_string"
 *   }
 * )
 */
class Form extends FormatterBase {

  /**
   * The number of times this formatter allows rendering the same entity.
   *
   * @var int
   */
  const RECURSIVE_RENDER_LIMIT = 20;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

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
   * The form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * An array of counters for the recursive rendering protection.
   *
   * Each counter takes into account all the relevant information about the
   * field and the referenced entity that is being rendered.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter::viewElements()
   *
   * @var array
   */
  protected static $recursiveRenderDepth = [];

  /**
   * Constructs a EntityReferenceEntityFormatter instance.
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
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LoggerChannelFactoryInterface $logger_factory,
    EntityHelper $entity_helper,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityFormBuilderInterface $entity_form_builder
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->loggerFactory = $logger_factory;
    $this->entityHelper = $entity_helper;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFormBuilder = $entity_form_builder;
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
      $container->get('entity.form_builder')
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
    $settings = [];
    $settings['form_mode'] = 'default';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['form_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getFormModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('Form mode'),
      '#default_value' => $this->getSetting('form_mode'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $field_name = $items->getName();

    foreach ($items as $delta => $field_item) {

      if ($field_name == 'field_form') {

        $target_entity_type_id = $this->getFieldSetting('target_type');
        $entity_type = $this->entityHelper->getDefinition($target_entity_type_id);
        $bundle_of_entity_type_id = $entity_type->getBundleOf();
        $bundle_of_entity_type = $this->entityHelper->getDefinition($bundle_of_entity_type_id);
        $bundle_key = $bundle_of_entity_type->getKey('bundle');
        $entity_storage_in_form = $this->entityHelper->getStorage($bundle_of_entity_type_id);

        $entity_in_form = $entity_storage_in_form->create([
          $bundle_key => $field_item->target_id,
        ]);

        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'entity--form',
            ],
          ],
        ];

        $build['form'] = $this->entityFormBuilder->getForm($entity_in_form, 'default');

        $elements[$delta] = $build;
      }
      elseif ($field_name == 'field_form_entity') {

        $new_account = \Drupal::service('entity.helper')->getStorage('user')->create([]);

        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'entity--form',
            ],
          ],
        ];

        $build['form'] = $this->entityFormBuilder->getForm($new_account, 'register');
        $elements[$delta] = $build;

      }
      elseif ($field_name == 'field_form_sys') {

        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'entity--form',
            ],
          ],
        ];

        $form_class = $field_item->value;
        $build['form'] = \Drupal::service('form_builder')->getForm($form_class);
        $elements[$delta] = $build;

      }

    }

    return $elements;
  }

}
