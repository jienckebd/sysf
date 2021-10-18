<?php

namespace Drupal\bd\Config;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Config\ConfigFactory as Base;
use Drupal\bd\Php\Arr;

/**
 * Extends core config factory.
 */
class ConfigFactory extends Base {

  /**
   * {@inheritDoc}
   */
  public function getSubconfig($config_name, $config_key, $allow_wildcard = TRUE) {

    if (!$config = $this->getConfig($config_name)) {
      return NULL;
    }

    $config_data = $config->getRawData();

    return $this->getSubconfigFromData($config_data, $config_key, $allow_wildcard);

  }

  /**
   * {@inheritDoc}
   */
  public function getSubconfigFromData(array $config_data, $config_key, $allow_wildcard = TRUE) {

    $config_key_pieces = explode('.', $config_key);
    $config_key_data = $config_data;
    foreach ($config_key_pieces as $config_key_piece) {

      if (isset($config_key_data[$config_key_piece])) {
        $config_key_data = $config_key_data[$config_key_piece];
      }
      elseif ($allow_wildcard && isset($config_key_data['*'])) {
        $config_key_data = $config_key_data['*'];
      }
      else {
        return NULL;
      }

    }

    return $config_key_data;

  }

  /**
   * {@inheritDoc}
   */
  public function setSubconfig($config_name, $config_key, $config_data) {
    $config = $this->getEditable($config_name);
    $current_config_data = $config->getRawData();
    $config_key_parents = explode('.', $config_key);
    NestedArray::setValue($current_config_data, $config_key_parents, $config_data);
    $config->setData($current_config_data);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($name, $key = NULL, $expand = TRUE, $strip_default_keys = FALSE, $return_as_array = FALSE) {
    $config = $this->doGet($name, FALSE, $expand);

    if ($return_as_array) {
      $config = $config->getRawData();
    }

    if ($strip_default_keys) {

    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigInContext($name, $context_type, $context_id, $key = NULL, $expand = TRUE, $strip_default_keys = FALSE, $return_as_array = FALSE) {
    return $this->getConfig($name, $key, $expand, $strip_default_keys, $return_as_array);
  }

  /**
   * {@inheritdoc}
   */
  public function getFromObject($object, $key) {

    if (!method_exists($object, 'get')) {
//      \Drupal::logger('todo')->warning("Object does not have get method.");
      return FALSE;
    }

    $key_pieces = explode('.', $key);
    $first_key = array_shift($key_pieces);

    if (!$data = $object->get($first_key)) {
      return FALSE;
    }

    if (!empty($data) && (count($key_pieces) >= 1)) {
      foreach ($key_pieces as $key_piece) {
        if (!empty($data[$key_piece])) {
          $data = $data[$key_piece];
        }
        elseif (!empty($data['*'])) {
          $data = $data['*'];
        }
      }
    }

    $this->recurseExpandConfigDeriver($data);

    return $data;
  }

  /**
   * Returns a configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   * @param bool $immutable
   *   (optional) Create an immutable configuration object. Defaults to TRUE.
   * @param bool $expand
   *   Whether or not to expand the config derivers.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function doGet($name, $immutable = TRUE, $expand = TRUE) {
    if ($config = $this->doLoadMultiple([$name], $immutable, $expand)) {
      return $config[$name];
    }
    else {
      // If the configuration object does not exist in the configuration
      // storage, create a new object.
      $config = $this->createConfigObject($name, $immutable);

      if ($immutable) {
        // Get and apply any overrides.
        $overrides = $this->loadOverrides([$name]);
        if (isset($overrides[$name])) {
          $config->setModuleOverride($overrides[$name]);
        }
        // Apply any settings.php overrides.
        if (isset($GLOBALS['config'][$name])) {
          $config->setSettingsOverride($GLOBALS['config'][$name]);
        }
      }

      foreach ($this->configFactoryOverrides as $override) {
        $config->addCacheableDependency($override->getCacheableMetadata($name));
      }

      return $config;
    }
  }

  /**
   * Returns a list of configuration objects for the given names.
   *
   * @param array $names
   *   List of names of configuration objects.
   * @param bool $immutable
   *   (optional) Create an immutable configuration objects. Defaults to TRUE.
   * @param bool $expand
   *   Expand the config derivers.
   *
   * @return \Drupal\Core\Config\Config[]|\Drupal\Core\Config\ImmutableConfig[]
   *   List of successfully loaded configuration objects, keyed by name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE, $expand = TRUE) {
    $list = [];

    foreach ($names as $key => $name) {
      $cache_key = $this->getConfigCacheKey($name, $immutable);
      if (isset($this->cache[$cache_key])) {
        $list[$name] = $this->cache[$cache_key];
        unset($names[$key]);
      }
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      // Initialise override information.
      $module_overrides = [];
      $storage_data = $this->storage->readMultiple($names);

      // Process config derivers.
      if (!empty($storage_data) && $expand) {
        $this->recurseExpandConfigDeriver($storage_data);
      }

      if ($immutable && !empty($storage_data)) {
        // Only get module overrides if we have configuration to override.
        $module_overrides = $this->loadOverrides($names);
      }

      foreach ($storage_data as $name => $data) {
        $cache_key = $this->getConfigCacheKey($name, $immutable);

        $this->cache[$cache_key] = $this->createConfigObject($name, $immutable);
        $this->cache[$cache_key]->initWithData($data);
        if ($immutable) {
          if (isset($module_overrides[$name])) {
            $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
          }
          if (isset($GLOBALS['config'][$name])) {
            $this->cache[$cache_key]->setSettingsOverride($GLOBALS['config'][$name]);
          }
        }

        $this->propagateConfigOverrideCacheability($cache_key, $name);

        $list[$name] = $this->cache[$cache_key];
      }
    }

    return $list;
  }

  /**
   * @param array $data
   * @param array $parents
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function recurseExpandConfigDeriver(array &$data, array &$parents = []) {

    foreach ($data as $key => &$value) {
      if (!is_array($value)) {
        continue;
      }
      if (empty($value['deriver_plugin'])) {
        $parents[] = $key;
        $this->recurseExpandConfigDeriver($value, $parents);
      }
      else {

        $deriver_id = $value['deriver_plugin'];
        $deriver_definition = $value['definition'];

        // Remove from original definition. Derivatives will be added in its
        // place.
        unset($data[$key]);

        $plugin_manager = \Drupal::service('plugin.manager.data_deriver');
        $plugin_data_deriver = $plugin_manager->createInstance($deriver_id, $deriver_definition);
        $derived_data = $plugin_data_deriver->process($data);

        if (!empty($derived_data)) {
          foreach ($derived_data as $derived_id => $derived_definition) {
            // @todo derived ID needs to be unique across derivers.
            $data[$derived_id] = $derived_definition;
          }
        }

      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function bulkUpdateKeys(array $updates) {

    // Iterate over all configs.
    $updated_config_names = [];

    $all_configs = $this->listAll();

    foreach ($this->doLoadMultiple($all_configs, FALSE) as $name => $config) {

      $config_data = $config->getRawData();

      // Iterate over all updates.
      foreach ($updates as $old_key => $new_key) {

        // Check if old key exists in config.
        if (!$current_values = Arr::get($config_data, $old_key)) {
          continue;
        }

        // If so, move to new key in config.
        Arr::set($config_data, $new_key, $current_values);
        Arr::unset($config_data, $old_key);

        // Set new config data and save.
        $config->setData($config_data);
        $config->save();

        $updated_config_names[] = $name;

      }

    }

    return $updated_config_names;

  }

  /**
   * @param array $data
   *
   * @return \Drupal\bd\Config\Config
   */
  public function getFromData(array $data) {
    $config = new Config('_custom', $this->storage, $this->eventDispatcher, $this->typedConfigManager);
    $config->initWithData($data);
    return $config;
  }

  /**
   * Creates a configuration object.
   *
   * @param string $name
   *   Configuration object name.
   * @param bool $immutable
   *   Determines whether a mutable or immutable config object is returned.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  protected function createConfigObject($name, $immutable) {
    if ($immutable) {
      return new ImmutableConfig($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
    }
    return new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection($name) {
    return $this->doGet($name);
  }

}
