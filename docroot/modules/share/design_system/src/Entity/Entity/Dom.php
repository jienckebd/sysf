<?php

namespace Drupal\design_system\Entity\Entity;

use Drupal\bd\Entity\Entity\Content as Base;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileSystemInterface;
use luizbills\CSS_Generator\Generator as CSSGenerator;

/**
 * Provides DOM entity.
 */
class Dom extends Base {

  /**
   * {@inheritDoc}
   */
  public function label() {
    $label = parent::label();

    if (!$label) {

      if ($this->hasField('dom')) {
        if (!$this->get('dom')->isEmpty()) {
          /** @var \Drupal\design_system\Entity\Entity\Dom $entity_subject */
          $entity_subject = $this->dom->entity;
          $label = $entity_subject->label();
        }
      }
    }

    if (!$label) {
      if ($this->hasField('field_device')) {
        if (!$this->get('field_device')->isEmpty()) {
          /** @var \Drupal\design_system\Entity\Entity\Dom $entity_device */
          $entity_device = $this->field_device->entity;
          $label = t('Responsive: @device', [
            '@device' => $entity_device->label(),
          ])->__toString();
        }
      }
    }

    return $label;
  }

  /**
   * {@inheritDoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this->bundle() == 'collection_color') {
      $this->processEffect();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($this->hasAsset()) {
      $this->buildCss();
    }

  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function processEffect() {
    $this->processEffectColorBrightness();
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function processEffectColorBrightness() {

    if ($this->get('field_effect')->isEmpty()) {
      return;
    }

    $derivatives = [];

    foreach ($this->get('field_effect') as $delta => $field_item) {

      $entity = $field_item->entity;
      $derivatives[$entity->id()] = $entity;

    }

    $entity_storage_dom = $this->getentityHelper()->getStorage('dom');

    // Clear out derived values.
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_items_color */
    $field_items_color = $this->get('field_color');
    $entity_colors = [];

    foreach ($field_items_color as $delta => $field_item_color) {
      /** @var \Drupal\design_system\Entity\Entity\Dom $entity_color */
      $entity_color = $field_item_color->entity;
      $entity_colors[$entity_color->id()] = $entity_color;
    }
    $this->set('field_color', []);

    foreach ($entity_colors as $entity_id_color => $entity_color) {
      /** @var \Drupal\design_system\Entity\Entity\Dom $entity_color */
      if (!$entity_color->get('base_entity')->isEmpty()) {
        unset($entity_colors[$entity_id_color]);
      }
    }

    foreach ($entity_colors as $entity_id_color => $entity_color) {
      /** @var \Drupal\design_system\Entity\Entity\Dom $entity_color */

      $color_value = $entity_color->get('color')->color;
      $field_items_color->appendItem([
        'target_id' => $entity_color->id(),
      ]);

      foreach ($derivatives as $entity_id_effect => $entity_effect) {
        /** @var \Drupal\design_system\Entity\Entity\Dom $entity_effect */

        $entity_values = [
          'base_entity' => $entity_color->id(),
          'field_effect' => $entity_effect->id(),
          'bundle' => 'color',
        ];

        if ($entity_color_derived = $entity_storage_dom->loadByProperties($entity_values)) {
          $entity_color_derived = reset($entity_color_derived);
        }
        else {
          $entity_color_derived = $entity_storage_dom->create($entity_values);
        }
        /** @var \Drupal\design_system\Entity\Entity\Dom $entity_color_derived */

        $entity_label_effect = $entity_effect->label();
        $entity_label_color = $entity_color->label();

        $entity_color_derived_label = "{$entity_label_color} ({$entity_label_effect})";
        $entity_color_derived->set('label', $entity_color_derived_label);

        $color_adjustment = $entity_effect->get('field_level')->value;
        $color_adjustment_decimal = $color_adjustment / 100;

        $color_value_derived = $this->adjustBrightness($color_value, $color_adjustment_decimal);
        $entity_color_derived->set('color', [
          'color' => $color_value_derived,
        ]);

        $entity_color_derived->save();
        $field_items_color->appendItem([
          'target_id' => $entity_color_derived->id(),
        ]);

      }

    }

  }

  /**
   * @param $hex_code
   * @param $adjust_percent
   *
   * @return string
   */
  protected function adjustBrightness($hex_code, $adjust_percent) {
    $hex_code = ltrim($hex_code, '#');

    if (strlen($hex_code) == 3) {
      $hex_code = $hex_code[0] . $hex_code[0] . $hex_code[1] . $hex_code[1] . $hex_code[2] . $hex_code[2];
    }

    $hex_code = array_map('hexdec', str_split($hex_code, 2));

    foreach ($hex_code as & $color) {
      $adjustableLimit = $adjust_percent < 0 ? $color : 255 - $color;
      $adjustAmount = ceil($adjustableLimit * $adjust_percent);

      $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
    }

    return '#' . implode($hex_code);
  }

  /**
   * {@inheritDoc}
   */
  public function delete() {
    parent::delete();

    if ($this->hasAsset()) {

      $file_system = $this->getFileSystem();
      $file_system->delete($this->getPathAsset());

    }

  }

  /**
   * @param array $element
   * @param string $attributes_key
   * @param string $attached_key
   * @param bool $is_nested
   */
  public function bindToElement(array &$element, $attributes_key = '#attributes', $attached_key = '#attached', $is_nested = FALSE) {

    $entity_id = $this->id();
    $dom_attribute_key = "data-dom-id-{$entity_id}";

    $element[$attributes_key][$dom_attribute_key] = TRUE;

    if (!$is_nested) {
      $element[$attached_key]['library'][] = $this->getlibraryName();
    }

    $map_nested = [
      'field_style',
    ];

    foreach ($map_nested as $nested_field_name) {

      if (!$this->hasField($nested_field_name)) {
        continue;
      }

      $field_items_nested = $this->get($nested_field_name);

      if ($field_items_nested->isEmpty()) {
        continue;
      }

      foreach ($field_items_nested as $delta => $field_item) {

        /** @var \Drupal\design_system\Entity\Entity\Dom $entity_nested */
        if (!$entity_nested = $field_item->entity) {
          continue;
        }

        $is_nested = TRUE;
        $entity_nested->bindToElement($element, $attributes_key, $attached_key, $is_nested);

      }

    }

  }

  /**
   * @return bool
   */
  public function hasAsset() {
    return ($this->bundle() == 'style');
  }

  /**
   * @param string $asset_type
   *
   * @return string
   */
  public function getlibraryName($asset_type = 'css') {
    $suffix = $this->getLibraryNameSuffix($asset_type);
    return "design_system/{$suffix}";
  }

  /**
   * @param string $asset_type
   *
   * @return string
   */
  public function getLibraryNameSuffix($asset_type = 'css') {
    $entity_id = $this->id();
    return "dom.{$entity_id}.{$asset_type}";
  }

  /**
   * @param string $asset_type
   *
   * @return string
   */
  public function getPathAsset($asset_type = 'css') {

    $entity_type_id = $this->getEntityTypeId();
    $entity_id = $this->id();

    $filename = "{$entity_id}.css";
    $path_dir = "public://design-system/auto/{$entity_type_id}";

    return "{$path_dir}/{$filename}";
  }

  /**
   * @param $asset_logic
   * @param string $asset_type
   *
   * @return false|int
   */
  public function writeAsset($asset_logic, $asset_type = 'css') {

    $path_css = $this->getPathAsset();

    $file_system = $this->getFileSystem();

    $path_css_directory = dirname($path_css);

    $file_system->prepareDirectory($path_css_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    return file_put_contents($path_css, $asset_logic);

  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildCss() {
    $css_build = $this->getCssBuildForEntity($this);

    $options = [
      'indentation' => '  ',
    ];

    $css_generator = new CSSGenerator($options);

    foreach ($css_build as $css_selector => $css_selector_style) {

      if (!empty($css_selector_style['media_query'])) {
        $css_generator->open_block('media', $css_selector_style['media_query']);
      }

      $css_generator->add_rule($css_selector, $css_selector_style['property']);
    }

    $css_generator->close_blocks();

    $css_output = $css_generator->get_output(FALSE);

    $this->writeAsset($css_output);

    \Drupal::cache('discovery')->delete('library_info:autotheme__21');

  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_context
   * @param null $selector
   *
   * @return array|false
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCssBuildForEntity(ContentEntityInterface $entity_context, $selector = NULL, $media_query_used = FALSE) {

    if (!$entity_context->hasField('index')) {
      $this->getLogger()->warning("Missing index.");
      return FALSE;
    }

    $entity_id = $entity_context->id();

    if (!$selector) {
      $selector = "[data-dom-id-{$entity_id}]";
    }

    if ($entity_context->hasField('field_modifier') && $entity_context->field_modifier->value) {
      $modifier = "";
    }
    else {
      $modifier = " ";
    }

    $selector .= "{$modifier}";

    // @todo get tag machine name derivative ID from entity reference.
    $field_items_dom_subject = $entity_context->get('dom');

    if (!$field_items_dom_subject->isEmpty()) {

      $entity_subject = $field_items_dom_subject->entity;
      $bundle_entity_subject = $entity_subject->bundle();

      switch ($bundle_entity_subject) {

        case 'tag_group':

          $field_items_dom_tag = $entity_subject->get('dom_tag');

          $tags = [];
          foreach ($field_items_dom_tag as $field_item_dom_tag) {

            $entity_dom_tag = $field_item_dom_tag->entity;
            $tags[] = $entity_dom_tag->label();

          }

          foreach ($tags as $tag) {
            $selector .= "{$tag},";
          }
          $selector = rtrim($selector, ',');
          break;

        case 'tag':

          $tag_machine_name = $entity_subject->label();
          $selector .= "{$tag_machine_name}";

          break;

        case 'element':

          $selector_base = $selector;
          $selector = "";

          if (!$entity_context->get('field_selector')->isEmpty()) {

            foreach ($entity_context->get('field_selector') as $delta => $field_item_selector) {

              /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_context_selector */
              $entity_context_selector = $field_item_selector->entity;

              $entity_context_selector_value = $entity_context_selector->label();
              $selector .= "{$selector_base} $entity_context_selector_value,";

            }

          }

          break;

        case 'pseudo_element':
        case 'pseudo_class':

          $value = $entity_subject->label();
          $selector = rtrim($selector, ' ');
          $selector .= "{$value}";

          break;

        default:

          $value = $entity_subject->label();
          $selector .= "{$value}";

          break;

      }
    }

    $entity_index_values = $entity_context->get('index')->getValue();

    $css_build = [];

    $skip_field_type = [
      'entity_index',
    ];

    foreach ($entity_index_values as $key => $entity_index_value) {

      // Get entity index from serialized string.
      $entity_index_raw = $entity_index_value['value'];
      $entity_index = unserialize($entity_index_raw);

      if (empty($entity_index)) {
        continue;
      }

      // Remove specified fields.
      $field_value_is_important = FALSE;
      if (!empty($entity_index['field_important'])) {
        $important = array_pop($entity_index['field_important']);
        $field_value_is_important = (bool) $important['value'];
      }

      foreach ($entity_index as $field_name => $field_value) {

        if (!$field_definition = $entity_context->getFieldDefinition($field_name)) {
          $this->getLogger()->warning("Missing field @field_name. Possibly recently deleted field.", [
            '@field_name' => $field_name,
          ]);
          continue;
        }

        if (!method_exists($field_definition, 'getThirdPartySettings')) {
          continue;
        }

        if (in_array($field_definition->getType(), $skip_field_type)) {
          continue;
        }

        $attribute_config = $field_definition->getThirdPartySettings('bd');

        $dom_config = $attribute_config['behavior']['dom'] ?? [];

        $attribute = $dom_config['attribute'] ?? NULL;
        $subattribute = $dom_config['subattribute'] ?? NULL;
        $field_config_is_important = !empty($dom_config['is_important']) ? TRUE : FALSE;
        $style_suffix = NULL;

        if (empty($subattribute)) {
          $this->getLogger()->warning("Missing subattribute on dom config to process CSS for field @field_name.", [
            '@field_name' => $field_name,
          ]);
          continue;
        }

        $subattribute_value = NULL;
        if (!empty($field_value[0]['target_id'])) {
          // This is used on entity reference fields.
          $target_entity_id = $field_value[0]['target_id'];
          $target_entity_type_id = $field_definition->getSetting('target_type');
          $target_entity = $this->getentityHelper()
            ->getStorage($target_entity_type_id)
            ->load($target_entity_id);

          if (empty($target_entity)) {
            $this->getLogger()->warning("Entity @entity_type / @entity_id not found.", [
              '@entity_type' => $target_entity_type_id,
              '@entity_id' => $target_entity_id,
            ]);
            continue;
          }

          if ($target_entity_type_id == 'font') {

            // Check font is activated.
            /** @var \Drupal\fontyourface\Entity\Font $target_entity */
            if ($target_entity->isDeactivated()) {
              $target_entity->activate();
            }

            $css_build[$selector]['property']['font-family'] = $target_entity->css_family->value;
            $css_build[$selector]['property']['font-weight'] = $target_entity->css_weight->value;
            $css_build[$selector]['property']['font-style'] = $target_entity->css_style->value;

          }
          elseif ($target_entity_type_id == 'dom' && $target_entity->bundle() == 'color') {
            $css_build[$selector]['property'][$subattribute] = $target_entity->color->color;
          }

        }
        else {
          $subattribute_value = $field_value[0]['value'];
        }

        if (is_null($subattribute_value)) {
          continue;
        }

        if ($field_config_is_important || $field_value_is_important) {
          $style_suffix = "!important";
        }

        if (!is_null($style_suffix)) {
          $subattribute_value = "{$subattribute_value} {$style_suffix}";
        }

        $css_build[$selector]['property'][$subattribute] = $subattribute_value;

      }
    }

    if (empty($media_query_used)) {
      if ($this->hasField('field_device') && ($entity_device = $entity_context->field_device->entity)) {
        $media_query = $this->getMediaQueryForDevice($entity_device);
        $css_build[$selector]['media_query'] = $media_query;
      }
    }

    if ($entity_context->hasField('field_style')) {
      foreach ($entity_context->get('field_style') as $delta => $entity_context_field_item) {

        $entity_context_child = $entity_context_field_item->entity;
        $entity_context_child_id = $entity_context_child->id();

        $selector = "[data-dom-id-{$entity_context_child_id}]{$selector}";

        if ($child_css_build = $this->getCssBuildForEntity($entity_context_child, $selector, $media_query_used)) {
          foreach ($child_css_build as $key => $value) {
            $css_build[$key] = $value;
          }
        }

      }
    }

    return $css_build;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_device
   *
   * @return string
   */
  public function getMediaQueryForDevice(ContentEntityInterface $entity_device) {
    $media_query = "";

    $media_types = [];
    if (!$entity_device->get('field_media_type')->isEmpty()) {
      foreach ($entity_device->get('field_media_type') as $delta => $field_item) {
        $media_types[] = $field_item->entity->label();
      }
    }

    if (!empty($media_types)) {
      $media_types_string = implode(', ', $media_types);
      $media_query .= $media_types_string;
    }

    if ($max_width = $entity_device->max_width->value) {
      $media_query .= " and (max-width: {$max_width})";
    }

    if ($min_width = $entity_device->min_width->value) {
      $media_query .= " and (min-width: {$min_width})";
    }

    return $media_query;
  }

  /**
   * @return \Drupal\Core\File\FileSystem
   */
  protected function getFileSystem() {
    /** @var \Drupal\Core\File\FileSystem $file_system */
    return \Drupal::service('file_system');
  }

  /**
   * @return \Drupal\bd\Entity\EntityHelper
   */
  protected function getentityHelper() {
    return \Drupal::service('entity.helper');
  }

  /**
   * @return \Psr\Log\LoggerInterface
   */
  protected function getLogger() {
    return \Drupal::logger('design_system');
  }

}
