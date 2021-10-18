<?php

namespace Sysf\Blt\Plugin\Commands\Platform;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class SyncCommand extends BaseCommand {

  /**
   * Sync a platform across environments, including database and managed files.
   *
   * @command platform:sync
   */
  public function info($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->success("stack:reset ran okay");
  }

}
