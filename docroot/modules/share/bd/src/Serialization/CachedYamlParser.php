<?php

namespace Drupal\bd\Serialization;

use Drupal\Component\Serialization\YamlSymfony as Base;

/**
 * Default serialization for YAML using the Symfony component.
 */
class CachedYamlParser extends Base {

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {

    if (!\Drupal::hasContainer()) {
      return parent::decode($raw);
    }

    $cid_yaml = 'yaml_parsed';

    $static_cache = &drupal_static(__FUNCTION__, []);
    if (empty($static_cache['cache_backend'])) {
      $static_cache['cache_backend'] = \Drupal::cache('default');
      $cached_data = $static_cache['cache_backend']->get($cid_yaml) ?: [];
      if (!empty($cached_data)) {
        $static_cache['yaml_parsed'] = $cached_data->data;
      }
      else {
        $static_cache['yaml_parsed'] = [];
      }
    }

    $parsed_id = hash('sha256', $raw);
    if (!empty($static_cache['yaml_parsed'][$parsed_id])) {
      $data = $static_cache['yaml_parsed'][$parsed_id];
    }
    else {
      $data = parent::decode($raw);

      /** @var \Drupal\Core\Cache\CacheBackendInterface $cache_backend */
      $cache_backend = $static_cache['cache_backend'];

      $static_cache['yaml_parsed'][$parsed_id] = $data;

      $cache_backend->set($cid_yaml, $static_cache['yaml_parsed']);
    }

    return $data;
  }

}
