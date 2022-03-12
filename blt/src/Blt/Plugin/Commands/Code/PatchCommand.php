<?php

namespace Sysf\Blt\Plugin\Commands\Code;

use Sysf\Blt\Plugin\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Defines the "code:patch" command.
 */
class PatchCommand extends BaseCommand {

  /**
   * Generate a patch based on changes to a dependency.
   *
   * @param array $options
   *   The command options.
   *
   * @command code:patch
   *
   * @description Generate a patch based on changes to a dependency.
   *
   * @aliases patch
   *
   * @throws \Exception
   */
  public function exec(array $options = [
    'name' => InputOption::VALUE_OPTIONAL,
    'path' => InputOption::VALUE_OPTIONAL,
  ]) {

    $inputs = [
      'name' => [
        'label' => "Composer dependency name",
      ],
      'path' => [
        'label' => "Patch output file path",
      ],
    ];

    $defaults = [];

    $options = $this->buildInput($inputs, $options, $defaults);

    $dependency_name = $options['name'];
    $target_path = $options['path'];

    $package_config = $this->getComposerLockDependency($dependency_name);

    $dependency_git_url = $package_config['source']['url'];
    $dependency_git_code_reference = $package_config['source']['reference'];

    $dependency_installed_path = $this->getDependencyInstalledPath($dependency_name);

    $dependency_tmp_path = "{$_ENV['SYS_PATH_TMP']}/autopatch/{$dependency_git_code_reference}";
    $this->taskExecStack()
      ->exec("rm -rf {$dependency_tmp_path}")
      ->exec("mkdir -p {$dependency_tmp_path}")
      ->exec("git clone {$dependency_git_url} {$dependency_tmp_path}")
      ->exec("cd {$dependency_tmp_path}")
      ->exec("git checkout {$dependency_git_code_reference}")
      ->exec("cp -R {$dependency_installed_path}/* {$dependency_tmp_path}/")
      ->run();

    $filename_remove = [
      'PATCHES.txt',
      'LICENSE.txt',
    ];
    foreach ($filename_remove as $filename) {
      $filename_path = "{$dependency_tmp_path}/{$filename}";
      if (file_exists($filename_path)) {
        $this->taskFilesystemStack()
          ->remove($filename_path)
          ->run();
      }
    }

    $this->taskExecStack()
      ->dir($dependency_tmp_path)
      ->exec("git checkout '*.info.yml'")
      ->exec("git add -A")
      ->exec("git diff --cached > {$target_path}")
      #->exec("rm -rf {$dependency_tmp_path}")
      ->run();

  }

  /**
   * @param $dependency_name
   *
   * @return mixed|null
   */
  public function getComposerLockDependency($dependency_name) {
    $return = NULL;
    $composer_lock = $this->getComposerLock();
    foreach ($composer_lock['packages'] as $delta => $package_config) {
      if ($package_config['name'] == $dependency_name) {
        $return = $package_config;
        break;
      }
    }
    return $return;
  }

  /**
   * @return mixed
   */
  public function getComposerLock() {
    $path_composer = "{$_ENV['SYS_PATH_ROOT']}/composer.lock";
    $composer_lock_raw = file_get_contents($path_composer);
    return json_decode($composer_lock_raw, TRUE);
  }

  /**
   * @param $dependency_name
   *
   * @return string
   */
  public function getDependencyInstalledPath($dependency_name) {

    // @todo base on installer paths using composer object.

    if ($dependency_name == 'drupal/core') {
      return "{$_ENV['SYS_PATH_ROOT']}/docroot/core";
    }

    $module_name = str_replace('drupal/', '', $dependency_name);
    return "{$_ENV['SYS_PATH_ROOT']}/docroot/modules/contrib/{$module_name}";

  }

}
