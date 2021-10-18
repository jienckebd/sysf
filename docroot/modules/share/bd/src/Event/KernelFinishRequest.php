<?php

namespace Drupal\bd\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Rules event.
 */
class KernelFinishRequest extends Event {

  const EVENT_NAME = 'bd.rules.kernel.finish_request';

  /**
   * KernelResponse constructor.
   */
  public function __construct() {
  }

}
