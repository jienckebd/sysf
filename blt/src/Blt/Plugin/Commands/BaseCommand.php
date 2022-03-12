<?php

namespace Sysf\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Account;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Servers;
use AcquiaCloudApi\Endpoints\Variables;
use Sysf\Blt\Traits\IoTrait;
use Robo\Config\Config;
use Symfony\Component\Console\Question\Question;
use Sysf\Blt\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Base class for sys commands.
 */
class BaseCommand extends BltTasks {

  use IoTrait;

  /**
   * The path to the root directory of the current platform.
   *
   * @var string
   */
  protected $pathPlatform;

  /**
   * The path to sys root.
   *
   * @var string
   */
  protected $pathSys;

  /**
   * The path to user home folder.
   *
   * @var string
   */
  protected $pathHome;

  /**
   * The path to this project.
   *
   * @var string
   */
  protected $pathProject;

  /**
   * This project name.
   *
   * @var string
   */
  protected $projectName;

  /**
   * The relative path of the docroot.
   *
   * @var string
   */
  protected $relPathDocroot;

  /**
   * The file system.
   *
   * @var \Sysf\Blt\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * This hook fires before each command.
   *
   * @hook init
   */
  public function hookInit() {

    $this->pathHome = getenv('HOME');
    $this->pathSys = "{$this->pathHome}/sys";
    $this->pathPlatform = $this->getConfigValue('repo.root');
    $this->pathProject = "{$this->pathHome}/sys/project/devops";
    $this->projectName = "sysf/devops";
    $this->relPathDocroot = "docroot";

    $this->fs = new Filesystem();

    $command_id = $this->input->getArgument('command');
    $this->notice("Running command: {$command_id}");

  }

  /**
   * This hook fires after each command.
   *
   * @hook post-command
   */
  public function hookPostCommand() {
    $command_id = $this->input->getArgument('command');
    $this->success("Finished running command: {$command_id}");
  }

  /**
   * @param array $config
   *
   * @throws \Exception
   */
  public function setConfigEnv(array $config) {
    $config_yaml = Yaml::dump($config);
    $this->fs->dumpFile($this->getPath('config_env'), $config_yaml);
  }

  /**
   * @return \Robo\Config\Config
   * @throws \Exception
   */
  public function getConfigEnv() {
    $path_config_env = $this->getPath('config_env');
    if ($this->fs->exists($path_config_env)) {
      $contents = file_get_contents($path_config_env);
      $config_raw_data = Yaml::parse($contents);
    }
    else {
      $config_raw_data = [];
    }
    return new Config($config_raw_data);
  }

  /**
   * @param array $config
   *
   * @throws \Exception
   */
  public function setConfigSys(array $config) {
    $config_yaml = Yaml::dump($config);
    $this->fs->dumpFile($this->getPath('config_sys'), $config_yaml);
  }

  /**
   * @return \Robo\Config\Config
   * @throws \Exception
   */
  public function getConfigSys() {
    $path_config = $this->getPath('config_sys');
    if ($this->fs->exists($path_config)) {
      $contents = file_get_contents($path_config);
      $config_raw_data = Yaml::parse($contents);
    }
    else {
      $config_raw_data = [];
    }
    return new Config($config_raw_data);
  }

  /**
   * @param null $path_id
   *
   * @return array|mixed|string
   * @throws \Exception
   */
  public function getPath($path_id = NULL) {
    $path = [];
    $path["config_env"] = "{$this->pathSys}/etc/env.yml";
    $path["config_sys"] = "{$this->pathSys}/project/devops/etc/sys.yml";
    $path["ssh"] = "{$this->pathHome}/.ssh";

    if (stripos($path_id, '.') !== FALSE) {
      $path_pieces = explode('.', $path_id);
      if (!empty($path_pieces[1])) {
        $path_type = $path_pieces[0];
        $path_id = $path_pieces[1];

        switch ($path_type) {

          case "platform";
            $path = "{$this->pathSys}/platform/{$path_id}";
            break;

          default:

            break;

        }
      }
      return $path;
    }

    if (!empty($path_id)) {
      if (empty($path[$path_id])) {
        throw new \Exception("Path is not defined: {$path_id}");
      }
      return $path[$path_id];
    }

    return $path;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getOptionEnvPlatform() {
    $config_env = $this->getConfigEnv();
    $option = [];
    foreach ($config_env->get('platform') as $platform_id => $platform_config) {
      $option[$platform_id] = $platform_id;
    }
    return $option;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getOptionSysPlatform() {
    $config_env = $this->getConfigSys();
    $option = [];
    foreach ($config_env->get('platform') as $platform_id => $platform_config) {
      $option[$platform_id] = $platform_id;
    }
    return $option;
  }

  /**
   * @param $platform_id_sys
   *
   * @return string
   * @throws \Exception
   */
  public function buildEnvPlatformId($platform_id_sys) {

    $config_env = $this->getConfigEnv();

    if (!$config_env->get("platform.{$platform_id_sys}")) {
      return $platform_id_sys;
    }

    $count = 2;
    while (TRUE) {
      $platform_id_env = "{$platform_id_sys}{$count}";
      if (!$config_env->get("platform.{$platform_id_env}")) {
        break;
      }
      $count++;
    }

    return $platform_id_env;
  }

  /**
   * @return false|string
   */
  public function getDateString() {
    return date("Y-m-d\TH:i:s") . "T";
  }

  /**
   * @param array $inputs
   * @param array $return
   * @param array $defaults
   *
   * @return array
   * @throws \Exception
   */
  protected function buildInput(array $inputs, array $return = [], array $defaults = []) {
    foreach ($inputs as $option_id => $option_config) {

      if (!empty($return[$option_id])) {
        continue;
      }

      $default = isset($defaults[$option_id]) ? $defaults[$option_id] : NULL;

      if (!empty($option_config['type'])) {

        switch ($option_config['type']) {

          case 'choice':
            if (empty($default)) {
              $default = key($option_config['choice']);
            }
            $return[$option_id] = $this->io()->choice($option_config['label'], $option_config['choice'], $default);
            break;

          default:
            throw new \Exception("Invalid input type: {$option_config['type']}");
            break;

        }

      }
      else {
        $return[$option_id] = $this->io()->askQuestion(new Question("Enter {$option_config['label']}", $default));
      }

    }
    return $return;
  }

  /**
   * @return \AcquiaCloudApi\Connector\Client
   * @throws \Exception
   */
  public function getAceApiClient(array $query = [], array $options = []) {

    $config_env = $this->getConfigEnv();

    $api_key = $config_env->get('ace_api_key');
    $api_secret = $config_env->get('ace_api_secret');

    $config = [
      'key' => $api_key,
      'secret' => $api_secret,
    ];

    $connector = new Connector($config);

    $client = Client::factory($connector);

    if (!empty($query)) {
      foreach ($query as $key => $value) {
        $client->addQuery($key, $value);
      }
    }

    if (!empty($options)) {
      foreach ($options as $key => $value) {
        $client->addOption($key, $value);
      }
    }

    return $client;
  }

  /**
   * @param array $query
   * @param array $options
   *
   * @return \AcquiaCloudApi\Endpoints\Account
   * @throws \Exception
   */
  public function getAceApiClientAccount(array $query = [], array $options = []) {
    return new Account($this->getAceApiClient($query, $options));
  }

  /**
   * @param array $query
   * @param array $options
   *
   * @return \AcquiaCloudApi\Endpoints\DatabaseBackups
   * @throws \Exception
   */
  public function getAceApiClientDatabaseBackups(array $query = [], array $options = []) {
    return new DatabaseBackups($this->getAceApiClient($query, $options));
  }

  /**
   * @param array $query
   * @param array $options
   *
   * @return \AcquiaCloudApi\Endpoints\Applications
   * @throws \Exception
   */
  public function getAceApiClientApplications(array $query = [], array $options = []) {
    return new Applications($this->getAceApiClient($query, $options));
  }

  /**
   * @param array $query
   * @param array $options
   *
   * @return \AcquiaCloudApi\Endpoints\Environments
   * @throws \Exception
   */
  public function getAceApiClientEnvironments(array $query = [], array $options = []) {
    return new Environments($this->getAceApiClient($query, $options));
  }

  /**
   * @param array $query
   * @param array $options
   *
   * @return \AcquiaCloudApi\Endpoints\Servers
   * @throws \Exception
   */
  public function getAceApiClientServers(array $query = [], array $options = []) {
    return new Servers($this->getAceApiClient($query, $options));
  }

  /**
   * @param array $query
   * @param array $options
   *
   * @return \AcquiaCloudApi\Endpoints\Variables
   * @throws \Exception
   */
  public function getAceApiClientVariables(array $query = [], array $options = []) {
    return new Variables($this->getAceApiClient($query, $options));
  }

  /**
   * @param $drush_alias_site_ids
   * @param $target_path
   *
   * @throws \Robo\Exception\TaskException
   */
  public function copyDrushAliasesToPath($drush_alias_site_ids, $target_path) {
    $api_client_account = $this->getAceApiClientAccount(['version' => 9]);
    $drush_aliases_stream = $api_client_account->getDrushAliases();
    $temp_path_dir = uniqid(mt_rand(), true);
    $temp_path = "{$_ENV['SYS_PATH_TMP']}/{$temp_path_dir}";

    $this->taskExecStack()
      ->exec("mkdir -p {$temp_path}")
      ->run();

    $temp_path_filename = "{$temp_path}/archive.tar.gz";

    file_put_contents($temp_path_filename, $drush_aliases_stream);

    // Extract archive.
    $this->taskExecStack()
      ->dir($temp_path)
      ->exec("tar -zxvf {$temp_path_filename}")
      ->run();

    foreach ($drush_alias_site_ids as $drush_alias_id) {
      $path_site_alias_source = "{$temp_path}/sites/{$drush_alias_id}.site.yml";
      $path_site_alias_target = "{$target_path}/{$drush_alias_id}.site.yml";

      if (!file_exists($path_site_alias_source)) {
        $this->warning("Source Drush alias path does not exist: {$path_site_alias_source}");
        continue;
      }

      $this->notice("Copying source drush alias from {$path_site_alias_source} to {$path_site_alias_target}");
      $this->fs->copy($path_site_alias_source, $path_site_alias_target);
    }

    $this->notice("Cleaning up: {$temp_path}");
    $this->taskExecStack()
      ->exec("rm -rf {$temp_path}")
      ->run();

  }

  /**
   * Sync a file for devops in to place.
   *
   * @param string $template_id
   *   The template ID.
   * @param string $target
   *   The target path.
   * @param array $variables
   *   The variables for the template.
   */
  public function renderTwigTemplateToFile($template_id, $target, array $variables = []) {
    if (!$this->fs->exists($target)) {
      $path_template = "{$this->pathProject}/template/{$template_id}";
      $this->fs->copy($path_template, $target, TRUE);
    }
  }

}
