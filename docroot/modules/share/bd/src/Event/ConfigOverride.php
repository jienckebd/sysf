<?php

namespace Drupal\bd\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired for config overrides.
 */
class ConfigOverride extends Event {

  const EVENT_NAME = 'bd.rules.config.override';

  /**
   * The config name.
   *
   * @var \Drupal\user\UserInterface
   */
  public $config_data;

  /**
   * The config overrides.
   *
   * @var array
   */
  protected $overrides;

  /**
   * ConfigOverrideEvent constructor.
   *
   * @param $config_name
   */
  public function __construct($config_name) {
    $this->config_data = $config_name;
  }

  /**
   * @param array $overrides
   *
   * @return array
   */
  public function setOverrides(array $overrides) {
    return $overrides;
  }

  /**
   * @return array
   */
  public function getOverrides() {
    return $this->overrides;
  }

}
