<?php

namespace Drupal\bd\Discovery;

/**
 * Discovery manager interface.
 */
interface ManagerInterface {

  /**
   * Get the discovery data from cache or process it.
   *
   * @param string $discovery_type
   *   The hook type.
   * @param bool $flatten
   *   Whether or not to flatten data and remove module names.
   * @param bool $reset
   *   Whether or not to reset cache.
   *
   * @return array
   *   The discovery data.
   */
  public function getDiscoveryData($discovery_type, $flatten = TRUE, $reset = FALSE);

}
