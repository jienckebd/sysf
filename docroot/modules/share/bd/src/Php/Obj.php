<?php

namespace Drupal\bd\Php;

use Drupal\bd\Component\Arrays\NestedArray;

/**
 * Extends PHP's object handling.
 */
class Obj {

  /**
   * @param mixed $object
   *
   * @return array
   * @throws \ReflectionException
   */
  public static function dismount($object) {
    $reflectionClass = new \ReflectionClass(get_class($object));
    $array = [];
    foreach ($reflectionClass->getProperties() as $property) {
      $property->setAccessible(TRUE);
      $array[$property->getName()] = $property->getValue($object);
      $property->setAccessible(FALSE);
    }

    if (isset($array['additional'])) {
      foreach ($array['additional'] as $key => $value) {
        if (isset($array[$key]) && is_array($value)) {
          $array[$key] = NestedArray::mergeDeep($array[$key], $value);
        }
        else {
          $array[$key] = $value;
        }
      }
      unset($array['additional']);
    }

    return $array;
  }

  /**
   * @param object $object
   *
   * @return array
   */
  public static function toArray(object $object) {

    $array_tmp = (array) $object;

    $array = [];

    foreach ($array_tmp as $key => $value) {

      // Strange symbols appear in key when casting object to array in some
      // scenarios. So make key alphanumeric.
      $new_key = preg_replace("/[^a-zA-Z0-9]+/", "", $key);

      $array[$new_key] = $value;
    }

    return $array;
  }

}
