<?php

/**
 * @file
 * Contains Drupal settings for local context.
 */

$_ENV['SYS_DEBUG_MODE'] = (bool) getenv('HOST_UID');
$_ENV['SYS_DEBUG_MODE'] = TRUE;

$config['clamav.settings']['enabled'] = FALSE;

/**
 * Disable purge locally.
 */
$config['purge.plugins']['purgers'] = [];

/**
 * Configure search_api server and index.
 */
$config['search_api.server.local']['status'] = TRUE;
$config['search_api.index.universe']['server'] = 'local';
$config['ultimate_cron.job.purge_processor_cron_cron']['status'] = FALSE;

/**
 * Enable local development services.
 */
if (!empty($_ENV['SYS_DEBUG_MODE'])) {
  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
}

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
if (!empty($_ENV['SYS_DEBUG_MODE'])) {
  $config['system.logging']['error_level'] = 'verbose';
}

/**
 * Disable CSS and JS aggregation.
 */
if (!empty($_ENV['SYS_DEBUG_MODE'])) {
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;
  $config['advagg.settings']['enabled'] = FALSE;
}

/**
 * Disable the render cache.
 *
 * Note: you should test with the render cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the render cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
if (!empty($_ENV['SYS_DEBUG_MODE'])) {
  $settings['cache']['bins']['render'] = 'cache.backend.null';
  $settings['cache']['bins']['page'] = 'cache.backend.null';
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
}

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

ini_set('memory_limit', '1024M');
ini_set('xdebug.max_nesting_level', '1024');

#$class_loader->addPsr4('Drupal\\webprofiler\\', ['/var/www/html/modules/contrib/devel/webprofiler/src']);
#$settings['container_base_class'] = '\Drupal\webprofiler\DependencyInjection\TraceableContainer';
