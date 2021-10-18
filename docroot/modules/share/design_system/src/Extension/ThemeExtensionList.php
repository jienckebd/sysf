<?php

namespace Drupal\design_system\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeEngineExtensionList;
use Drupal\Core\Extension\ThemeExtensionList as Base;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Extends core theme extension list.
 */
class ThemeExtensionList extends Base {

  use StringTranslationTrait;

  /**
   * Constructs a new ThemeExtensionList instance.
   *
   * @param string $root
   *   The app root.
   * @param string $type
   *   The extension type.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeEngineExtensionList $engine_list
   *   The theme engine extension listing.
   * @param string $install_profile
   *   The install profile used by the site.
   */
  public function __construct(
    $root,
    $type,
    CacheBackendInterface $cache,
    InfoParserInterface $info_parser,
    ModuleHandlerInterface $module_handler,
    StateInterface $state,
    ConfigFactoryInterface $config_factory,
    ThemeEngineExtensionList $engine_list,
    $install_profile
  ) {
    parent::__construct($root, $type, $cache, $info_parser, $module_handler, $state, $config_factory, $engine_list, $install_profile);
  }

  /**
   * {@inheritdoc}
   */
  protected function doList() {

    $themes = parent::doList();

    if (!$theme_auto = $this->getThemeAuto()) {
      return $themes;
    }

    $autotheme_info_dir_uri = "public://autotheme";
    $autotheme_info_dir_relative = ltrim(file_url_transform_relative(file_create_url($autotheme_info_dir_uri)), '/');
    if (!is_dir($autotheme_info_dir_uri)) {
      mkdir($autotheme_info_dir_uri, 0777);
    }

    foreach ($theme_auto as $entity_id => $theme_entity) {

      $theme_id = "autotheme__{$entity_id}";

      /** @var \Drupal\Core\Extension\Extension $base_theme */
      $base_theme = $themes['alpha'];

      $filename_info = "{$theme_id}.info.yml";
      $pathname = "{$autotheme_info_dir_relative}/{$filename_info}";
      $pathname_absolute = "{$autotheme_info_dir_uri}/{$filename_info}";
      if (!is_file($pathname_absolute)) {
        $theme_info = [
          'name' => $theme_entity->label(),
          'core' => '8.x',
          'type' => 'theme',
          'base theme' => $base_theme->getName(),
        ];
        $theme_info_yaml = Yaml::encode($theme_info);
        file_put_contents($pathname_absolute, $theme_info_yaml);
      }

      $derived_theme = new Extension($this->root, 'theme', $pathname, NULL);

      $derived_theme->subpath = $base_theme->subpath;

      $copy_properties = [
        'info',
        'owner',
        'prefix',
        'requires',
        'required_by',
        'sort',
      ];

      foreach ($copy_properties as $property) {
        $derived_theme->{$property} = $base_theme->{$property};
      }

      // Inherit status from current core.extension config.
      if (in_array($theme_id, array_keys($this->installedThemes))) {
        $derived_theme->status = 1;
      }
      else {
        $derived_theme->status = 0;
      }

      $derived_theme->info['name'] = $theme_entity->label() ?: $theme_id;
      $derived_theme->info['description'] = $theme_entity->description->value ?: "";
      $derived_theme->info['base theme'] = 'alpha';
      $derived_theme->base_themes['alpha'] = 'alpha';
      $derived_theme->module_dependencies = [];

      if ($entity_layout = $theme_entity->field_layout->entity) {
        $layout_config = \Drupal::service('design.system')->getLayoutEntityConfig($entity_layout);
        $derived_theme->info['regions'] = [];
        foreach ($layout_config['row'] as $row_config) {
          foreach ($row_config['region'] as $region_id => $region_config) {
            $derived_theme->info['regions'][$region_id] = $region_config['label'];
          }
        }
      }

      if ($media_entity = $theme_entity->field_image->entity) {

        if ($file_entity = $media_entity->field_media_image->entity) {

          // @todo image_style
          $file_url = $file_entity->createFileUrl(TRUE);
          $file_url = ltrim($file_url, '/');
          $derived_theme->info['screenshot'] = $file_url;

        }

      }

      $themes[$theme_id] = $derived_theme;

    }

    return $themes;
  }

  /**
   *
   */
  public function getThemeAuto() {
    return \Drupal::service('entity.helper')->getStorage('theme_entity')->loadByProperties([
      'bundle' => 'default',
    ]);
  }

}
