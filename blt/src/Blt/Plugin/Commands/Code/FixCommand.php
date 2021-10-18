<?php

namespace Sysf\Blt\Plugin\Commands\Code;

use Sysf\Blt\Plugin\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

/**
 * Defines the "code:fix" command.
 */
class FixCommand extends BaseCommand {

  /**
   * Attempt to fix code based on Drupal coding standards.
   *
   * @param array $options
   *   The command options.
   *
   * @command code:fix
   *
   * @throws \Exception
   */
  public function exec($options = [
    'path' => InputOption::VALUE_OPTIONAL,
  ]) {

    if (empty($options['path'])) {
      $options['path'] = $this->io()->askQuestion(new Question("Enter a path to scan"));
    }

    $cmd = "{$_ENV['SYS_PATH_ROOT']}/vendor/bin/phpcs --config-set installed_paths ~/.composer/vendor/drupal/coder/coder_sniffer";
    $this->taskExec($cmd)
      ->dir($_ENV['SYS_PATH_ROOT'])
      ->run();

    $cmd = "{$_ENV['SYS_PATH_ROOT']}/vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md {$options['path']}";
    $this->taskExec($cmd)
      ->dir($_ENV['SYS_PATH_ROOT'])
      ->run();

  }

}
