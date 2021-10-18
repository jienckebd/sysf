<?php

namespace Sysf\Blt\Plugin\Commands\App;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class SyncCommand extends BaseCommand {

  /**
   * Syncs a single app.
   *
   * @command app:sync
   */
  public function info($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->success("stack:reset ran okay");
  }

}
