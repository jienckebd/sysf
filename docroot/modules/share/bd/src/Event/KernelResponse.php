<?php

namespace Drupal\bd\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Rules event.
 */
class KernelResponse extends Event {

  const EVENT_NAME = 'bd.rules.kernel.response';

  /**
   * KernelResponse constructor.
   */
  public function __construct() {
  }

}
