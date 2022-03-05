<?php

namespace Drupal\bd\Element;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\bd\Config\TypedConfigManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a config schema subform.
 *
 * @RenderElement("config_schema_subform")
 */
class ConfigSchemaSubform extends FormElement implements ContainerFactoryPluginInterface {

  /**
   * The typed config manager.
   *
   * @var \Drupal\bd\Config\TypedConfigManagerInterface
   */
  protected $bdTypedConfigManager;

  /**
   * The default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The config logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ConfigSchemaSubform constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TypedConfigManagerInterface $typed_config_manager,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->typedConfigManager = $typed_config_manager;
    $this->cache = $cache;
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
      $container->get('bd.config.typed'),
      $container->get('cache.default'),
      $container->get('logger.channel.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#config_schema_id' => NULL,
      '#config_data' => [],
      '#entity' => NULL,
      '#is_new' => FALSE,
      '#process' => [
        [static::class, 'processGroup'],
        [$this, 'processConfigSchemaElement'],
      ],
      '#pre_render' => [
        [static::class, 'preRenderGroup'],
      ],
      '#element_validate' => [
        [static::class, 'validateConfigSchemaElement'],
      ],
      '#value_callback' => [
        [static::class, 'valueCallback'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $complete_form
   *
   * @return array
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function processConfigSchemaElement(array $element, FormStateInterface $form_state, array &$complete_form) {

    $config_schema_id = $element['#config_schema_id'];
    $config_data = $element['#config_data'];

    if (!$config_schema = $this->typedConfigManager->getDefinition($config_schema_id)) {
      return $element;
    }

    if (empty($config_schema['mapping'])) {
      return $element;
    }

    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $config_schema_object */
    $typed_data_parent = $this->typedConfigManager->createFromNameAndData($config_schema_id, $config_data);

    if (!empty($element['#entity'])) {
      $typed_data_parent->setContext('entity', $element['#entity']->getTypedData());
    }

    $is_new = $element['#is_new'];

    $this->recurseProcessConfigSchemaMapping($element, $form_state, $config_schema, $config_data, $typed_data_parent, $is_new);

    return $element;
  }

  /**
   * @param array $element
   * @param $form_state
   * @param $config_schema
   * @param $config_data
   * @param \Drupal\Core\TypedData\TypedDataInterface|null $typed_data_parent
   * @param bool $is_new
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function recurseProcessConfigSchemaMapping(array &$element, $form_state, &$config_schema, &$config_data, TypedDataInterface $typed_data_parent = NULL, $is_new = TRUE) {

    if (!empty($config_schema['form']['group'])) {
      foreach ($config_schema['form']['group'] as $group_id => $group_config) {
        $element[$group_id] = [
          '#type' => isset($group_config['type']) ? $group_config['type'] : 'details',
          '#title' => isset($group_config['label']) ? $group_config['label'] : $this->t('Group'),
          '#tree' => TRUE,
          '#weight' => isset($group_config['weight']) ? $group_config['weight'] : 0,
        ];
        if ($element[$group_id]['#type'] == 'details') {
          $element[$group_id]['#open'] = isset($group_config['open']) ? $group_config['open'] : FALSE;
        }
      }
    }

    foreach ($config_schema['mapping'] as $property_name => $property_config_schema) {
      if (!empty($property_config_schema['hidden'])) {
        continue;
      }

      if (isset($config_data[$property_name])) {
        $property_value = $config_data[$property_name];
      }
      elseif ($is_new && isset($property_config_schema['default_value'])) {
        $property_value = $property_config_schema['default_value'];
      }
      else {
        $property_value = NULL;
      }

      if (in_array($property_name, TypedConfigManagerInterface::CONFIG_KEY_DEFAULT)) {
        continue;
      }

      $parents = $element['#parents'];
      $parents[] = $property_name;

      $data_type = $property_config_schema['type'];
      $typed_data_definition = $this->typedConfigManager->createTypedDataDefinition($data_type, $property_config_schema, $property_name, $property_value, $typed_data_parent);

      if (in_array($data_type, ['field_definition.third_party_settings'])) {
        if (empty($property_config_schema['mapping'])) {
          $subproperty_schema = $this->typedConfigManager->getDefinition($data_type);
          $property_config_schema['type'] = 'mapping';
          $property_config_schema['mapping'] = $subproperty_schema['mapping'];
          $data_type = 'mapping';
        }
      }

      if ($data_type == 'mapping') {
        if (isset($property_config_schema['label'])) {
          $element[$property_name] = [
            '#type' => 'details',
            '#title' => isset($property_config_schema['label']) ? $this->t($property_config_schema['label']) : NULL,
            '#description' => isset($property_config_schema['description']) ? $this->t($property_config_schema['description']) : NULL,
            '#open' => isset($property_config_schema['open']) ? $property_config_schema['open'] : FALSE,
            '#tree' => TRUE,
            '#parents' => $parents,
          ];
        }
        else {
          $element[$property_name] = [
            '#type' => 'container',
            '#tree' => TRUE,
            '#parents' => $parents,
          ];
        }

        $this->recurseProcessConfigSchemaMapping($element[$property_name], $form_state, $property_config_schema, $property_value, $typed_data_definition);
        continue;
      }
      elseif ($data_type == 'sequence') {

        $element[$property_name] = [
          '#type' => 'details',
          '#title' => $this->t($property_config_schema['label']),
          '#description' => isset($property_config_schema['description']) ? $this->t($property_config_schema['description']) : NULL,
          '#open' => isset($property_config_schema['open']) ? $property_config_schema['open'] : FALSE,
          '#tree' => TRUE,
          '#parents' => $parents,
        ];

        $ajax_wrapper_sequence = implode("--", $element[$property_name]['#parents']);
        $ajax_wrapper_sequence = "ajax--wrapper--{$ajax_wrapper_sequence}";
        $element[$property_name]['#prefix'] = '<div id="' . $ajax_wrapper_sequence . '">';
        $element[$property_name]['#suffix'] = '</div>';

        $button_parents = $element[$property_name]['#parents'];
        $button_parents[] = 'actions';
        $button_parents[] = 'add';
        $add_sequence_delta_button_name = implode('_', $button_parents);

        if (!$sequence_count = $form_state->get($add_sequence_delta_button_name)) {
          if (!empty($property_value)) {
            $sequence_count = count($property_value);
            $sequence_count++;
          }
          else {
            $sequence_count = 1;
          }
        }

        $sequence_delta = 0;
        while ($sequence_delta < $sequence_count) {

          $sequence_parents = $element['#parents'];
          $sequence_parents[] = $sequence_delta;

          $element[$property_name][$sequence_delta] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'sequence',
              ],
              'data-sequence-delta' => $sequence_delta,
            ],
            '#tree' => TRUE,
            '#parents' => $sequence_parents,
          ];

          $subconfig = isset($config_data[$property_name][$sequence_delta]) ? $config_data[$property_name][$sequence_delta] : [];

          $sequence_child_data_type = $property_config_schema['sequence']['type'];
          $sequence_config_schema = $property_config_schema['sequence'];

          $this->attachChildElement($typed_data_definition, $element[$property_name][$sequence_delta], $form_state, $property_name, $subconfig, $sequence_child_data_type, $sequence_config_schema, $is_new);

          $sequence_delta++;
          continue;
        }

        $element[$property_name]['actions'] = [
          '#type' => 'actions',
        ];

        $element[$property_name]['actions']['add'] = [
          '#type' => 'submit',
          '#title' => $this->t('Add another item'),
          '#button_type' => 'secondary',
          '#button_size' => 'sm',
          '#submit' => [[$this, 'submitFormSequenceAdd']],
          '#ajax' => [
            'callback' => [$this, 'ajaxOpSequenceAdd'],
            'wrapper' => $ajax_wrapper_sequence,
          ],
          '#limit_validation_errors' => [],
        ];

        $element[$property_name]['actions']['add']['#name'] = $add_sequence_delta_button_name;

        continue;
      }

      $this->attachChildElement($typed_data_definition, $element, $form_state, $property_name, $property_value, $data_type, $property_config_schema);

    }

  }

  /**
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data_definition
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $property_name
   * @param $property_value
   * @param $data_type
   * @param array $property_config_schema
   * @param bool $is_new
   */
  protected function attachChildElement(TypedDataInterface $typed_data_definition, array &$element, FormStateInterface $form_state, $property_name, $property_value, $data_type, array $property_config_schema, $is_new = TRUE) {

    if (in_array($data_type, ['mapping'])) {
      $this->recurseProcessConfigSchemaMapping($element, $form_state, $property_config_schema, $property_value, $typed_data_definition);
      return;
    }

    $parents = $element['#parents'];
    $parents[] = $property_name;

    $form_element_values = $plugin_wrapper = $form_state->getValue($parents) ?: $property_value;

    $element[$property_name] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'config-schema--property--wrapper',
        ],
        'data-property-name' => $property_name,
      ],
      '#tree' => TRUE,
      '#parents' => $parents,
      '#weight' => isset($property_config_schema['weight']) ? $property_config_schema['weight'] : 0,
    ];

    if (isset($property_config_schema['group'])) {
      $element[$property_name]['#group'] = $property_config_schema['group'];
    }

    if ($property_config_schema['type'] == 'plugin_instance') {
      /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
      $plugin_type_manager = \Drupal::service('plugin.plugin_type_manager');

      $plugin_type = $plugin_type_manager->getPluginType($property_config_schema['plugin_type']);

      $plugin_manager = \Drupal::service($plugin_type->getPluginManagerServiceName());

      $options_plugin = [];
      foreach ($plugin_manager->getDefinitions() as $plugin_id_all => $plugin_definition_all) {
        if (!is_array($plugin_definition_all)) {
          continue;
        }
        if (isset($plugin_definition_all['label'])) {
          $plugin_label = "{$plugin_definition_all['label']} ({$plugin_id_all})";
        }
        elseif (isset($plugin_definition_all['admin_label'])) {
          $plugin_label = "{$plugin_definition_all['admin_label']} ({$plugin_id_all})";
        }
        else {
          $plugin_label = $plugin_id_all;
        }
        $options_plugin[$plugin_id_all] = $plugin_label;
      }

      $unique_ajax_id = "ajax-update-id--";
      $unique_ajax_id .= implode('--', $parents);
      $unique_ajax_id = Html::cleanCssIdentifier($unique_ajax_id);

      $element[$property_name]['#attributes']['id'] = $unique_ajax_id;

      $element[$property_name]['plugin_id'] = [
        '#type' => 'select',
        '#normalize' => TRUE,
        '#title' => $typed_data_definition->getDataDefinition()->getLabel(),
        '#options' => $options_plugin,
        '#parents' => array_merge($parents, ['plugin_id']),
        '#ajax' => [
          'callback' => '\Drupal\bd\Element\ConfigSchemaSubform::ajaxOpPluginSelectUpdate',
          'wrapper' => $unique_ajax_id,
        ],
      ];

      if (!empty($form_element_values['plugin_id'])) {

        $element[$property_name]['plugin_id']['#default_value'] = $form_element_values['plugin_id'];

        $plugin_config = $form_element_values['plugin_configuration'] ?? [];

        $plugin_instance = $plugin_type->getPluginManager()
          ->createInstance($plugin_wrapper['plugin_id'], $plugin_config);

        $element[$property_name]['plugin_configuration'] = $plugin_instance->buildConfigurationForm($form_state->getCompleteForm(), $form_state);
        $element[$property_name]['plugin_configuration']['#parents'] = array_merge($parents, ['plugin_configuration']);
      }
    }
    else {

      $map_form_element_type = [
        'boolean' => 'checkbox',
        'string' => 'textfield',
      ];

      $form_element_type = $map_form_element_type[$data_type] ?? 'textarea';
      $label = $typed_data_definition->getDataDefinition()->getLabel();
      $description = $typed_data_definition->getDataDefinition()->getDescription();

      $element[$property_name] = [
        '#type' => $form_element_type,
        '#title' => $label,
        '#description' => $description,
        '#parents' => $parents,
        '#default_value' => $form_element_values,
        '#required' => $property_config_schema['required'] ?? FALSE,
        '#group' => $property_config_schema['group'] ?? NULL,
      ];

    }

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function ajaxOpPluginSelectUpdate(array $form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];

    array_pop($parents);

    $element = NestedArray::getValue($form, $parents);

    return $element;
  }

  /**
   * @param $element
   * @param $input
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return $input;
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function validateConfigSchemaElement(array &$element, FormStateInterface $form_state) {
  }

}
