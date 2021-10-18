<?php

namespace Sysf\Blt\Plugin\Commands\Stack;

use Symfony\Component\Console\Input\InputOption;
use Robo\Contract\VerbosityThresholdInterface;
use Sysf\Blt\Plugin\Commands\BaseCommand;

/**
 * Defines commands in the "stack:reset:*" namespace.
 */
class ResetCommand extends BaseCommand {

  /**
   * Stops and removes all containers, images, and volumes with force.
   *
   * @command stack:reset
   */
  public function reset($options = [
    'ni' => FALSE,
  ]) {
    $this->notice("Resetting all docker containers, images, and volumes.");
    $this->taskExecStack()
      ->dir($this->platformRoot)
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->stopOnFail()
      ->exec("docker rm -f $(docker ps -a -q)")
      ->exec("docker volume rm $(docker volume ls -q)")
      ->exec("docker rmi $(docker images -a -q)")
      ->run();
    $this->success("stack:reset ran okay");
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

  /**
   * Stops and removes all docker images with force.
   *
   * @command stack:reset:image
   */
  public function resetImage($options = [
    'ni' => FALSE,
  ]) {
  }

  /**
   * Stops and removes all docker volumes with force.
   *
   * @command stack:reset:volume
   */
  public function resetVolume($options = [
    'ni' => FALSE,
  ]) {
  }

}
