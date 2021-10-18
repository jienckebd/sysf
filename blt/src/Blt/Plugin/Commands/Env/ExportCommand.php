<?php

namespace Sysf\Blt\Plugin\Commands\Env;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class ExportCommand extends BaseCommand {

  /**
   * Exports all environment data to a single configuration file.
   *
   * @command env:export
   */
  public function export($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->success("stack:reset ran okay");
  }

}
