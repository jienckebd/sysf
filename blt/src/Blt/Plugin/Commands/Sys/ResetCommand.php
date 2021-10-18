<?php

namespace Sysf\Blt\Plugin\Commands\Sys;

use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:init:*" namespace.
 */
class ResetCommand extends BaseCommand {

  /**
   * Resets the full system.
   *
   * @command sys:reset
   */
  public function reset($options = [
    'ni' => FALSE,
  ]) {
    $this->say("Resetting all docker containers, images, and volumes.");
    $this->taskExecStack()
      ->dir($this->platformRoot)
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->stopOnFail()
      ->exec("docker rm -f $(docker ps -a -q)")
      ->exec("docker volume rm $(docker volume ls -q)")
      ->exec("docker rmi $(docker images -a -q)")
      ->run();
    $this->yell("sys:reset ran okay");
  }

}
