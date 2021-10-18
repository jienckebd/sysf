<?php

namespace Sysf\Blt\Plugin\Composer;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin {

  /**
   * Runs when packages installed or updated.
   *
   * @param mixed $event
   *   The event.
   */
  public static function scaffold($event) {

    $platform_root = dirname(dirname(dirname(__DIR__)));
    $vendor_drush_path = "{$platform_root}/vendor/bin/drush";

    if (file_exists($vendor_drush_path)) {
      unlink($vendor_drush_path);
    }
  }

}
