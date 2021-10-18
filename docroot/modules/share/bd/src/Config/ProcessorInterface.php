<?php

namespace Drupal\bd\Config;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Discovery manager interface.
 */
interface ProcessorInterface {

  /**
   * @param array $array
   * @param $plugin_type_id
   * @param $plugin_id
   * @param $plugin_config
   * @param $plugin_contexts
   */
  public function processArray(array &$array, $plugin_type_id, $plugin_id, array &$plugin_config, array &$plugin_contexts);

  /**
   * Process a hook using config.
   *
   * @param string $discovery_type
   *   The hook.
   * @param array $contexta
   *   The data of the cook.
   * @param array $contextb
   *   If hook has 2 arguments, this is the second.
   */
  public function processConfigHook($discovery_type, array &$contexta, array &$contextb = NULL);

  /**
   * @param array $mapping
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  public function processMapping(array &$mapping, EntityTypeInterface $entity_type, EntityInterface $entity = NULL);

}
