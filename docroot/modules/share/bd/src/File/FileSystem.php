<?php

namespace Drupal\bd\File;

use Drupal\Core\File\FileSystem as Base;

/**
 * Provides helpers to operate on files and stream wrappers.
 */
class FileSystem extends Base implements FileSystemInterface {

  /**
   * @param $asset_relative_path
   *
   * @return array
   */
  public function getModuleListWithAsset($asset_relative_path) {

    $module_handler = \Drupal::moduleHandler();

    $module_list = [];

    foreach ($module_handler->getModuleDirectories() as $module_name => $module_directory) {

      $path_check = "{$module_directory}/{$asset_relative_path}";
      if (!$this->exists($path_check)) {
        continue;
      }

      $module_list[$module_name] = $path_check;

    }

    return $module_list;
  }

  /**
   * @param $file_path
   *
   * @return false|string
   */
  public function loadFile($file_path) {

    if (!$this->exists($file_path)) {
      return FALSE;
    }

    return file_get_contents($file_path);
  }

  /**
   * @param $path
   *
   * @return bool
   */
  public function exists($path) {
    return file_exists($path);
  }

}
