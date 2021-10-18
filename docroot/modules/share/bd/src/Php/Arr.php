<?php

namespace Drupal\bd\Php;

/**
 * Extends PHP's array handling.
 */
class Arr {

  /**
   * @param array $array
   * @param $search
   * @param $replace
   * @param false $match_word_boundary
   */
  public static function replace(array &$array, $search, $replace, $match_word_boundary = FALSE) {

    $search_processed = [];
    foreach ($search as $key => $value) {
      $search_processed[$key] = "{{ {$value} }}";
    }

    static::replacePlain($array, $search_processed, $replace);
  }

  /**
   * @param array $array
   * @param $search
   * @param $replace
   * @param false $match_word_boundary
   */
  public static function replacePlain(array &$array, $search, $replace, $match_word_boundary = FALSE) {

    if ($match_word_boundary) {
      // @todo learn more about regex.
    }

    $array = json_decode(str_replace($search, $replace, json_encode($array)), TRUE);
  }

  /**
   * Get an item from an array.
   *
   * @param array $array
   * @param string|array $key
   * @param mixed|null $default
   * @param bool $key_exists
   *
   * @return mixed|null
   */
  public static function &get(array &$array, $key, $default = NULL, &$key_exists = NULL) {
    if (is_string($key)) {
      $parents = explode('.', $key);
    }
    else {
      $parents = $key;
    }
    $ref = &$array;
    foreach ($parents as $parent) {
      if (is_array($ref) && (isset($ref[$parent]) || array_key_exists($parent, $ref))) {
        $ref = &$ref[$parent];
      }
      else {
        $key_exists = FALSE;
        $null = NULL;
        return $null;
      }
    }
    $key_exists = TRUE;
    return $ref;
  }

  /**
   * Checks if an item is available in an array.
   *
   * @param array $array
   * @param string|array $key
   *
   * @return bool
   */
  public static function has(array $array, $key) {

    if (is_array($key)) {
      $key = implode('.', $key);
    }

    if (array_key_exists($key, $array)) {
      return TRUE;
    }
    if (strpos($key, '.') === FALSE) {
      return FALSE;
    }

    foreach (explode('.', $key) as $segment) {
      if (is_array($array) && array_key_exists($segment, $array)) {
        $array = $array[$segment];
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Set an array item to a given value using "dot" notation.
   *
   * @param array $value
   * @param string|array $key
   * @param mixed $value
   * @param bool $force
   *
   * @return array
   */
  public static function set(&$array, $key, $value, $force = FALSE) {

    if (is_string($key)) {
      $keys = explode('.', $key);
    }
    else {
      $keys = $key;
    }

    while (count($keys) > 1) {
      $key = array_shift($keys);
      if (!isset($array[$key]) || !is_array($array[$key])) {
        $array[$key] = [];
      }
      $array = &$array[$key];
    }

    $final_key = array_shift($keys);
    if (!$force && isset($array[$final_key])) {
      return $array;
    }

    $array[$final_key] = $value;

    return $array;
  }

  /**
   * Unset an item from an array and remove its parents if now empty.
   *
   * @param array $array
   * @param string|array $key
   * @param bool $key_existed
   */
  public static function unset(array &$array, $key, &$key_existed = NULL) {

    $parents = explode('.', $key);

    $unset_key = array_pop($parents);
    $ref = &self::get($array, $parents, NULL, $key_existed);
    if ($key_existed && is_array($ref) && (isset($ref[$unset_key]) || array_key_exists($unset_key, $ref))) {
      $key_existed = TRUE;
      unset($ref[$unset_key]);
    }
    else {
      $key_existed = FALSE;
    }

  }

  /**
   * @param $haystack
   *
   * @phphelper
   *
   * @return mixed
   */
  public static function removeEmpty($haystack, $make_null = FALSE) {
    foreach ($haystack as $key => $value) {
      if (is_array($value)) {
        $haystack[$key] = static::removeEmpty($haystack[$key]);
      }

      if (empty($haystack[$key]) && ($haystack[$key] !== 0) && ($haystack[$key] !== '0')) {
        if ($make_null) {
          $haystack[$key] = NULL;
        }
        else {
          unset($haystack[$key]);
        }
      }
    }

    return $haystack;
  }

  /**
   * @param $arr1
   * @param $arr2
   *
   * @return array
   */
  public static function recurseDiff($arr1, $arr2) {
    return array_map('unserialize', array_diff(array_map('serialize', $arr1), array_map('serialize', $arr2)));
  }

  /**
   * @param array $array
   * @param $values_set
   */
  public static function recurseSetValues(array &$array, $values_set) {
    foreach ($array as $key => &$value) {

      if (is_array($value)) {
        if (isset($value['#type'])) {
          foreach ($values_set as $key_set => $value_set) {
            $value[$key_set] = $value_set;
          }
        }
        static::recurseSetValues($value, $values_set);
      }

    }
  }

}
