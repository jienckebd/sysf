<?php

namespace Sysf\Blt\Plugin\Commands\Platform;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class InfoCommand extends BaseCommand {

  /**
   * Prints information about the platforms in this environment.
   *
   * @command platform:info
   */
  public function add($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->success("stack:reset ran okay");
  }

}
