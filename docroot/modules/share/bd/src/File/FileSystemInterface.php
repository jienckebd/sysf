<?php

namespace Drupal\bd\File;

use Drupal\Core\File\FileSystemInterface as Base;

/**
 * Extends core file system.
 */
interface FileSystemInterface extends Base {

  /**
   * @param $asset_relative_path
   *
   * @return array
   */
  public function getModuleListWithAsset($asset_relative_path);

  /**
   * @param $path
   *
   * @return bool
   */
  public function exists($path);

  /**
   * @param $file_path
   *
   * @return false|string
   */
  public function loadFile($file_path);

}
