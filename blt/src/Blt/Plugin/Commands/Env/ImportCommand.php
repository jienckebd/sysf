<?php

namespace Sysf\Blt\Plugin\Commands\Env;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class ImportCommand extends BaseCommand {

  /**
   * Imports an environment based on the export config from another.
   *
   * @command env:import
   */
  public function info($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->success("stack:reset ran okay");
  }

}
