<?php

/**
 * @file
 * Contains logic to run in both Drupal and CLI.
 */

/**
 * Define platform ID.
 */
$_ENV['SYS_PLATFORM_ID'] = 'bd';

/**
 * These will be set in local by docksal.yml and in CI by pipelines variables.
 */
$_ENV['SYS_CONTEXT'] = getenv('SYS_CONTEXT');
$_ENV['SYS_SUBCONTEXT'] = getenv('SYS_SUBCONTEXT');

/**
 * Define context and subcontext: prod, staging, dev, local
 */
if (empty($_ENV['SYS_CONTEXT'])) {
  // Local and CI are defined by environment variables from infra.
  $_ENV['SYS_CONTEXT'] = 'local';
  $_ENV['SYS_SUBCONTEXT'] = 'local';
}

/**
 * Define if this is a cloud environment.
 */
$_ENV['SYS_IS_CLOUD'] = ($_ENV['SYS_CONTEXT'] !== 'local');

/**
 * Define if this is an upper cloud environment (prod).
 */
$_ENV['SYS_IS_UPPER'] = ($_ENV['SYS_SUBCONTEXT'] === 'prd');

/**
 * Define if this is a lower environment on cloud (not local).
 */
$_ENV['SYS_IS_LOWER'] = ($_ENV['SYS_IS_CLOUD'] && empty($_ENV['SYS_IS_UPPER'])) ? TRUE : FALSE;

/**
 * Define common paths.
 */
$_ENV['SYS_PATH_ROOT'] = dirname(dirname(dirname(__FILE__)));
$_ENV['SYS_PATH_DOCROOT'] = "{$_ENV['SYS_PATH_ROOT']}/docroot";
$_ENV['SYS_PATH_CONFIG'] = "{$_ENV['SYS_PATH_ROOT']}/var/config";
$_ENV['SYS_PATH_CONFIG_DEPLOY'] = "{$_ENV['SYS_PATH_ROOT']}/var/deploy/config";
$_ENV['SYS_PATH_CONTENT'] = "{$_ENV['SYS_PATH_ROOT']}/var/deploy/content";
$_ENV['SYS_PATH_DEPLOY_CONFIG'] = "{$_ENV['SYS_PATH_ROOT']}/var/deploy/deploy.yml";
$_ENV['SYS_PATH_ETC'] = "{$_ENV['SYS_PATH_ROOT']}/etc";
$_ENV['SYS_PATH_VAR'] = "{$_ENV['SYS_PATH_ROOT']}/var";
$_ENV['SYS_PATH_CACHE'] = "{$_ENV['SYS_PATH_ROOT']}/.cache";
$_ENV['SYS_PATH_PRIVATE'] = $_ENV['SYS_PATH_PRIVATE'] ?? "/app/files-private/private";
$_ENV['SYS_PATH_TMP'] = $_ENV['SYS_PATH_TMP'] ?? "{$_ENV['SYS_PATH_PRIVATE']}/tmp";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
