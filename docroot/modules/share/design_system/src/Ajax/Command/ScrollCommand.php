<?php

namespace Drupal\design_system\Ajax\Command;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for scrolling.
 *
 * @ingroup ajax
 */
class ScrollCommand implements CommandInterface {

  /**
   * The target selector.
   *
   * @var string
   */
  protected $selector;

  /**
   * The offset.
   *
   * @var int
   */
  protected $offset;

  /**
   * The speed.
   *
   * @var int
   */
  protected $speed;

  /**
   * ScrollCommand constructor.
   *
   * @param $selector
   * @param $offset
   * @param $speed
   */
  public function __construct($selector, $offset = 0, $speed = 500) {
    $this->selector = $selector;
    $this->offset = (int) $offset;
    $this->speed = (int) $speed;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'scroll',
      'selector' => $this->selector,
      'offset' => $this->offset,
      'speed' => $this->speed,
    ];
  }

}
