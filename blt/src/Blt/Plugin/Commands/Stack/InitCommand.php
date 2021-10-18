<?php

namespace Sysf\Blt\Plugin\Commands\Stack;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:init:*" namespace.
 */
class InitCommand extends BaseCommand {

  /**
   * Starts or creates all required stack services.
   *
   * @command stack:init
   */
  public function init($options = [
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
    $this->yell("stack:reset ran okay");
  }

  /**
   * Stops and removes all docker containers with force.
   *
   * @command stack:reset:container
   */
  public function resetContainer($options = [
    'ni' => FALSE,
  ]) {
  }

}
