<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\design_system\DesignSystem;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "button",
 *   label = @Translation("Button(s)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class Button extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a new LinkFormatter.
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
   *   Third party settings.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    DesignSystem $design_system,
    PathValidatorInterface $path_validator
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->designSystem = $design_system;
    $this->pathValidator = $path_validator;
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
      $container->get('design.system'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type' => 'primary',
      'size' => 'md',
      'icon' => NULL,
      'icon_position' => 'before',
      'icon_size' => NULL,
      'class' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $settings = $this->getSettings();
    $elements += $this->designSystem->buildConfigFormElementButton($settings);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the title field value is available, use it for the link text.
      if (!empty($item->title)) {
        // Unsanitized token replacement here because the entire link title
        // gets auto-escaped during link generation in
        // \Drupal\Core\Utility\LinkGenerator::generate().
        $link_title = \Drupal::token()->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
      }

      $element[$delta] = [
        '#type' => 'link',
        '#title' => $link_title,
        '#options' => $url->getOptions(),
      ];
      $element[$delta]['#url'] = $url;

      if (!empty($item->_attributes)) {
        $element[$delta]['#options'] += ['attributes' => []];
        $element[$delta]['#options']['attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
      if (empty($element[$delta]['#options']['attributes'])) {
        $element[$delta]['#options']['attributes'] = [];
      }

      if ($entity->hasField('field_icon') && $entity->getEntityTypeId() == 'component') {
        if ($entity_icon = $entity->field_icon->icon_name) {
          $settings['icon'] = $entity_icon;

          if ($icon_settings = $entity->field_icon->settings) {
            $icon_settings = unserialize($icon_settings);
            if (empty($settings['icon_size']) && !empty($icon_settings['size'])) {
              $settings['icon_size'] = $icon_settings['size'];
            }
          }

          if ($style = $entity->field_icon->style) {
            $settings['icon_style'] = $style;
          }

        }
      }

      if ($entity->hasField('field_button_size')) {
        if ($entity_button_size = $entity->field_button_size->value) {
          $settings['size'] = $entity_button_size;
        }
      }

      if ($entity->hasField('field_icon_color_bg')) {
        if ($icon_color_bg = $entity->field_icon_color_bg->color) {
          $settings['icon_color_bg'] = $icon_color_bg;
        }
      }

      if ($entity->hasField('field_button_type')) {
        if ($entity_button_type = $entity->field_button_type->value) {
          $settings['type'] = $entity_button_type;
        }
      }

      if ($entity->hasField('field_icon_position')) {
        if ($entity_icon_position = $entity->field_icon_position->value) {
          $settings['icon_position'] = $entity_icon_position;
        }
      }

      $this->designSystem->processConfigButton($element[$delta], $settings);
      $element[$delta]['#options']['attributes'] = $element[$delta]['#attributes'];
      unset($element[$delta]['#attributes']);
    }

    $element['#type'] = 'container';

    return $element;
  }

  /**
   * Builds the \Drupal\Core\Url object for a link field item.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link field item being rendered.
   *
   * @return \Drupal\Core\Url
   *   A Url object.
   */
  protected function buildUrl(LinkItemInterface $item) {
    $url = $item->getUrl() ?: Url::fromRoute('<none>');

    $options = $item->options;
    $options += $url->getOptions();
    $url->setOptions($options);

    return $url;
  }

}
