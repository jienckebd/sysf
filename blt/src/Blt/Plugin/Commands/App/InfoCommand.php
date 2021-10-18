<?php

namespace Sysf\Blt\Plugin\Commands\App;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class InfoCommand extends BaseCommand {

  /**
   * Prints info about apps in the current environment.
   *
   * @command app:info
   */
  public function info($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->success("stack:reset ran okay");
  }

}
