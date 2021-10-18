<?php

namespace Drupal\design_system\Extension;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\Exception\UninstalledExtensionException;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandler as Base;

/**
 * Extends core theme handler with entities.
 */
class ThemeHandler extends Base implements ThemeHandlerInterface {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  protected $entityHelper;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new ThemeHandler.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get the installed themes.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   A extension discovery instance.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(
    $root,
    ConfigFactoryInterface $config_factory,
    ThemeExtensionList $theme_list
  ) {
    parent::__construct($root, $config_factory, $theme_list);
    $this->entityHelper = \Drupal::service('entity.helper');
    $this->cache = \Drupal::cache('default');
    $this->logger = \Drupal::logger('design_system');
  }

  /**
   * {@inheritdoc}
   */
  public function getUiConfig() {
    return $this->configFactory->get('bd.ui');
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeName() {
    return \Drupal::service('theme.manager')->getActiveTheme()->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThemeName() {
    $default = $this->configFactory->get('system.theme')->get('default');
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminThemeName() {
    $default = $this->configFactory->get('system.theme')->get('admin');
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeEntity() {
    if (!$theme_entity_id = $this->getActiveThemeEntityId()) {
      return FALSE;
    }
    return $this->getThemeEntity($theme_entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminThemeEntity() {
    if (!$theme_entity_id = $this->getAdminThemeEntityId()) {
      return FALSE;
    }
    return $this->getThemeEntity($theme_entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThemeEntity() {
    if (!$theme_entity_id = $this->getDefaultThemeEntityId()) {
      return FALSE;
    }
    return $this->getThemeEntity($theme_entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeEntityId() {
    $theme_name = $this->getActiveThemeName();
    return $this->parseThemeEntityIdFromName($theme_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminThemeEntityId() {
    $theme_name = $this->getAdminThemeName();
    return $this->parseThemeEntityIdFromName($theme_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThemeEntityId() {
    $theme_name = $this->getDefaultThemeName();
    return $this->parseThemeEntityIdFromName($theme_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledThemeName() {
    return $this->configFactory->get('core.extension')->get('theme');
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeNameFromEntityId($theme_entity_id) {
    return "autotheme__{$theme_entity_id}";
  }

  /**
   * @param $theme_name
   *
   * @return bool
   */
  public function parseThemeEntityIdFromName($theme_name) {

    if (stripos($theme_name, 'autotheme__') === FALSE) {
      return FALSE;
    }

    [$nothing, $theme_entity_id] = explode('__', $theme_name);
    if (!is_numeric($theme_entity_id)) {
      return FALSE;
    }

    return $theme_entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeEntity($entity_id) {
    return $this->entityHelper
      ->getStorage(static::ENTITY_TYPE_ID_THEME)
      ->load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllThemeEntity() {
    return $this->entityHelper
      ->getStorage(static::ENTITY_TYPE_ID_THEME)
      ->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getColorPalette(ContentEntityInterface $theme_entity = NULL) {

    if (empty($theme_entity)) {
      // Default to the default theme.
      if (!$theme_entity = $this->getDefaultThemeEntity()) {
        $config = $this->getUiConfig();
        return $config->get("color_palette.default");
      }
    }

    if (!$entity_color_palette = $theme_entity->get(static::FIELD_NAME_COLOR_PALETTE)->entity) {
      $config = $this->getUiConfig();
      return $config->get("color_palette.default");
    }

    $color_palette = [];

    $color_palette[] = '#000000';
    $color_palette[] = '#ffffff';

    return $color_palette;
  }

  /**
   * @param $hex_code
   * @param $adjust_percent
   *
   * @return string
   */
  public function adjustBrightness($hex_code, $adjust_percent) {
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
   * {@inheritdoc}
   */
  public function getLayoutEntity(ContentEntityInterface $theme_entity = NULL) {
    if (empty($theme_entity)) {
      // Default to the default theme.
      if (!$theme_entity = $this->getDefaultThemeEntity()) {
        return FALSE;
      }
    }

    $field_items_layout = $theme_entity->get(static::FIELD_NAME_LAYOUT);

    if ($field_items_layout->isEmpty()) {
      return FALSE;
    }

    return $field_items_layout->get(0)->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionEntityForTheme(ContentEntityInterface $theme_entity = NULL) {
    if (empty($theme_entity)) {
      // Default to the default theme.
      if (!$theme_entity = $this->getDefaultThemeEntity()) {
        return FALSE;
      }
    }

    if (!$layout_entity = $this->getLayoutEntity($theme_entity)) {
      return FALSE;
    }

    return $this->getRegionEntityForLayout($layout_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionEntityForLayout(ContentEntityInterface $layout_entity = NULL) {
    if (empty($layout_entity)) {
      // Default to the default theme.
      if (!$layout_entity = $this->getLayoutEntity()) {
        return FALSE;
      }
    }

    $field_items_region = $layout_entity->get(static::FIELD_NAME_REGION);

    if ($field_items_region->isEmpty()) {
      return FALSE;
    }

    $regions = [];
    foreach ($field_items_region as $delta => $field_item) {
      $regions[$delta] = $field_item->entity;
    }

    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpointEntityForTheme(ContentEntityInterface $theme_entity = NULL) {
    return $this->getThemeEntityDependency(static::FIELD_NAME_BREAKPOINT, $theme_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDomTagEntityForTheme(ContentEntityInterface $theme_entity = NULL) {
    return $this->getThemeEntityDependency(static::FIELD_NAME_DOM_TAG, $theme_entity);
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDomDependencyEntityForTheme(ContentEntityInterface $theme_entity = NULL) {

    $map_field_name_dom_entity = [
      'field_dom_tag',
      'field_tag_group',
      'field_selector',
    ];

    $return = [];

    foreach ($map_field_name_dom_entity as $field_name) {
      if ($entities = $this->getThemeEntityDependency($field_name, $theme_entity)) {
        foreach ($entities as $entity) {
          $return[$entity->id()] = $entity;
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentEntityForTheme(ContentEntityInterface $theme_entity = NULL) {
    return $this->getThemeEntityDependency(static::FIELD_NAME_COMPONENT, $theme_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeEntityDependency($field_name, ContentEntityInterface $theme_entity = NULL) {
    if (empty($theme_entity)) {
      // Default to the default theme.
      if (!$theme_entity = $this->getActiveThemeEntity()) {

        // No theme entity set. Get defaults.
        $map_config_key_field_name = [
          'field_breakpoint' => 'breakpoint',
          'field_layout' => 'layout',
          'field_dom_tag' => 'dom_tag',
          'field_tag_group' => 'tag_group',
          'field_selector' => 'selector',
        ];

        $config_key = $map_config_key_field_name[$field_name];

        $config = $this->getUiConfig();

        if (!$defaults = $config->get("{$config_key}.default")) {
          return FALSE;
        }

        $dom_storage = $this->entityHelper->getStorage('dom');

        $entities = [];
        foreach ($defaults as $default_entity_id) {
          if ($breakpoint_entity = $dom_storage->load($default_entity_id)) {
            $entities[$default_entity_id] = $breakpoint_entity;
          }
        }

        return $entities;
      }
    }

    $field_items = $theme_entity->get($field_name);

    if ($field_items->isEmpty()) {
      return FALSE;
    }

    $entities = [];
    foreach ($field_items as $delta => $field_item) {

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_referenced */
      $entity_referenced = $field_item->entity;

      if ($entity_referenced->getEntityTypeId() == 'dom' && $entity_referenced->bundle() == 'collection') {

        if ($referenced_doms = $this->recurseGetDomReference($entity_referenced)) {

          foreach ($referenced_doms as $referenced_dom) {
            $entities[$referenced_dom->id()] = $referenced_dom;
          }

        }

      }
      else {
        $entities[$field_item->target_id] = $field_item->entity;
      }

    }

    return $entities;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return array
   */
  protected function recurseGetDomReference(ContentEntityInterface $entity) {

    $return = [];

    if (!$entity->hasField('dom')) {
      return $return;
    }

    foreach ($entity->get('dom') as $delta => $field_item) {

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_referenced */
      $entity_referenced = $field_item->entity;

      if ($entity_referenced->bundle() == 'style') {
        $return[$entity_referenced->id()] = $entity_referenced;
      }

      if ($child_references = $this->recurseGetDomReference($entity_referenced)) {
        foreach ($child_references as $child_reference) {
          $return[$child_reference->id()] = $child_reference;
        }
      }

    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdmin($name) {
    $list = $this->listInfo();
    if (!isset($list[$name])) {
      throw new UninstalledExtensionException("$name theme is not installed.");
    }
    $this->configFactory->getEditable('system.theme')
      ->set('admin', $name)
      ->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function processThemeDelete(ContentEntityInterface $theme) {

    // Check if theme is installed.
    $theme_name = $this->getThemeNameFromEntityId($theme->id());

    $ui_config = $this->getUiConfig();

    // If theme was default, reset default theme.
    if ($theme_name == $this->getDefaultThemeName()) {
      $new_default_theme = $ui_config->get('default_base_theme');
      $this->setDefault($new_default_theme);
    }

    // If theme was admin, reset admin theme.
    if ($theme_name == $this->getAdminThemeName()) {
      $new_admin_theme = $ui_config->get('default_admin_theme');
      $this->setAdmin($new_admin_theme);
    }

    // Last step is to uninstall theme.
    if ($this->themeExists($theme_name)) {
      $this->uninstall([$theme_name]);
    }

  }

}
