<?php

namespace Drupal\bd\Php;

/**
 * Extends PHP's string handling.
 */
class Str {

  /**
   * @param $machine_name
   *
   * @return string|string[]|null
   */
  public static function sanitizeMachineName($machine_name) {
    $machine_name = mb_strtolower($machine_name);
    $machine_name = preg_replace('@[^a-z0-9_.]+@', '_', $machine_name);
    return $machine_name;
  }

  /**
   * @param $string
   *
   * @return mixed|string
   */
  public static function toLabel($string) {
    $label = str_replace(["_", '-'], [" "], $string);
    $label = ucwords($label);
    return $label;
  }

}
