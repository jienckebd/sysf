<?php

namespace Sysf\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Sysf\Blt\Plugin\Traits\IoTrait;

/**
 * Base class for sys commands.
 */
class BaseCommand extends BltTasks {

  use IoTrait;

  /**
   * The path to the root directory of the current platform.
   *
   * @var string
   */
  protected $platformRoot;

  /**
   * This hook will fire for all commands in this command file.
   *
   * @hook init
   */
  public function initialize() {
    $this->platformRoot = $this->getConfigValue('repo.root');
  }

}
