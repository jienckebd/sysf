<?php

namespace Sysf\Blt\Plugin\Commands\App;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class DeleteCommand extends BaseCommand {

  /**
   * Deletes an existing app from a platform.
   *
   * @command app:delete
   */
  public function delete($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->success("stack:reset ran okay");
  }

}
