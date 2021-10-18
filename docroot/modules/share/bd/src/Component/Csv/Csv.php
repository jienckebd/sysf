<?php

namespace Drupal\bd\Component\Csv;

use League\Csv\Reader;
use Drupal\bd\Component\Yaml\Yaml;

/**
 * Class Csv.
 */
class Csv {

  /**
   * @param $path
   *
   * @return array
   */
  public static function toAssoc($path) {

    $csv = Reader::createFromPath($path, 'r');

    $results = $csv->fetchAssoc();

    $id_key = 'machine_name';

    $data = [];
    foreach ($results as $key => $result) {

      $item = [];

      foreach ($result as $subkey => $subvalue) {

        $subvalue = strip_tags($subvalue);

        if (empty($subvalue) || $subvalue === 0) {
          continue;
        }

        if (in_array($subkey, ['allowed_values', 'validators', 'components', 'tag', 'dom_tag'])) {
          $subvalue = explode("\n", $subvalue);
        }

        if (is_string($subvalue) && ($subkey == 'Description') && (stripos($subvalue, '.') === FALSE)) {
          $subvalue .= ".";
        }

        $subkey = strtolower(str_replace(" ", "_", $subkey));

        $item[$subkey] = $subvalue;
      }

      $data[$result[$id_key]] = $item;
    }

    return $data;
  }

  /**
   * @param $path
   * @param $path_yaml
   *
   * @return bool|int
   */
  public static function toYamlPath($path, $path_yaml) {
    if (!$data = static::toAssoc($path)) {
      return FALSE;
    }

    $yaml = Yaml::dump($data, 2, 2);
    return file_put_contents($path_yaml, $yaml);
  }

}
