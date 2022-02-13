<?php

// @codingStandardsIgnoreFile

use Drupal\bd\Serialization\CachedYamlParser;

require DRUPAL_ROOT . "/../vendor/acquia/blt/settings/blt.settings.php";

$databases = [];

$config_directories = [];

$settings['hash_salt'] = getenv('HASH_SALT') ?: 'wIpexdHvQeKnveWCYlPINYPyqsuuFwMtNyoATuylYJvMmkrpEcAMCIUakRLFbcSH';

$settings['update_free_access'] = FALSE;

$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;

$settings['install_profile'] = 'standard';

/**
 * Define Drupal paths.
 */
$settings["config_sync_directory"] = "../config/default";
$settings['file_temp_path'] = $_ENV['SYS_PATH_TMP'];
$settings['file_private_path'] = $_ENV['SYS_PATH_PRIVATE'];
$settings['php_storage']['twig']['directory'] = "{$_ENV['SYS_PATH_PRIVATE']}";

/**
 * Set file paths.
 */
$settings['file_public_path'] = 'sites/default/files';
# $settings['file_private_path'] = "{$settings['file_public_path']}/private";

// Required by plupload and for cron to delete 0 status files.
// @see https://www.drupal.org/project/plupload_widget/issues/2986932#comment-13182304
$config['file.settings']['make_unused_managed_files_temporary'] = TRUE;

/**
 * Configure search_api server and index.
 */
// $config['search_api.server.local']['status'] = FALSE;
// $config['search_api.index.universe']['server'] = $_ENV['SYS_SUBCONTEXT'];

/**
 * Override Drupal's YAML parser to use persistent caching.
 *
 * This provides a significant performance improvement because YAML parsing is
 * extremely expensive and output should be cached in core.
 */
$settings['yaml_parser_class'] = CachedYamlParser::class;

/**
 * Tuned cache config.
 */
$redis_host = getenv('SYS_REDIS_HOST') ?: 'redis';
$settings['redis.connection']['interface'] = 'PhpRedisCluster';
$settings['redis.connection']['password'] = "drupal";
$settings['redis.connection']['seeds'] = [];

$redis_node_count = getenv('SYS_REDIS_NODE_COUNT') ?: 1;
$redis_node_count = (int) $redis_node_count;
for ($n = 0; $n < $redis_node_count; $n++) {
  $settings['redis.connection']['seeds'][] = "redis-cluster-{$n}.{$redis_host}:6379";
}


$settings['redis.connection']['read_timeout'] = 120;
$settings['redis.connection']['timeout'] = 120;

$settings['cache']['default'] = 'cache.backend.database';
$cache_backend = 'cache.backend.database';

$settings['cache']['bins']['advagg'] = $cache_backend;
$settings['cache']['bins']['bootstrap']= $cache_backend;
$settings['cache']['bins']['config']= $cache_backend;
$settings['cache']['bins']['container']= $cache_backend;
$settings['cache']['bins']['data']= $cache_backend;
$settings['cache']['bins']['default']= $cache_backend;
$settings['cache']['bins']['entity']= $cache_backend;
$settings['cache']['bins']['file_mdm']= $cache_backend;
$settings['cache']['bins']['group_permission']= $cache_backend;
$settings['cache']['bins']['jsonapi_normalizations']= $cache_backend;
$settings['cache']['bins']['library']= $cache_backend;
$settings['cache']['bins']['menu']= $cache_backend;
$settings['cache']['bins']['migrate']= $cache_backend;
$settings['cache']['bins']['rest']= $cache_backend;
$settings['cache']['bins']['signal']= $cache_backend;
$settings['cache']['bins']['toolbar']= $cache_backend;
$settings['cache']['bins']['ultimate_cron_logger']= $cache_backend;

/**
 * Load context specific settings, either local, CI, or cloud.
 *
 * Each context can load its own subcontext settings. For example, cloud context
 * loads dev, staging, and prod subcontext.
 */
$path_settings_context = "{$_ENV['SYS_PATH_ROOT']}/etc/context/{$_ENV['SYS_CONTEXT']}/settings.php";
if (is_file($path_settings_context)) {
  require_once "${path_settings_context}";
}

/**
 * Load subcontext specific settings.
 *
 * For example, this means loading separate settings for either dev, staging,
 * or prod within ./etc/context/cloud/subcontext.
 */
$path_settings_subcontext = "{$_ENV['SYS_PATH_ROOT']}/etc/context/{$_ENV['SYS_CONTEXT']}/subcontext/{$_ENV['SYS_SUBCONTEXT']}.php";
if (is_file($path_settings_subcontext)) {
  require_once "${$path_settings_subcontext}";
}

ini_set('memory_limit', '1024M');

// Define legacy database.

$databases['default']['default']['init_commands'] = [
  'sql_mode' => "SET sql_mode = ''",
];

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

$databases['default']['default'] = [
  'database' => getenv("SYS_DB_NAME") ?: "drupal",
  'username' => getenv("SYS_DB_USER") ?: "drupal",
  'password' => getenv("SYS_DB_PASS") ?: "drupal",
  'host' => getenv("SYS_DB_HOST") ?: "database",
  'driver' => getenv("SYS_DB_DRIVER") ?: "mysql",
  'port' => getenv("SYS_DB_PORT") ?: "3306",
  'prefix' => getenv("SYS_DB_PREFIX") ?: "",
];

/**
 * Config overrides.
 */
$config['openid_connect.settings.keycloak']['settings']['client_id'] = getenv('SYS_OPENID_CLIENT_ID');
$config['openid_connect.settings.keycloak']['settings']['client_secret'] = getenv('SYS_OPENID_SECRET');

/**
 * IMPORTANT.
 *
 * Do not include additional settings here. Instead, add them to settings
 * included by `blt.settings.php`. See BLT's documentation for more detail.
 *
 * @link https://docs.acquia.com/blt/
 */
