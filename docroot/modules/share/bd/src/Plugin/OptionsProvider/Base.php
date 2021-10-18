<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\bd\Plugin\EntityPluginBase;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Base class for options providers.
 */
abstract class Base extends EntityPluginBase implements OptionsProviderInterface {

  /**
   * EntityPluginBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   */
  public function __construct(
    array $configuration = [],
    $plugin_id = NULL,
    $plugin_definition = [],
    EntityHelper $entity_helper = NULL,
    TypedConfigManagerInterface $typed_config_manager = NULL
  ) {
    $this->entityHelper = $entity_helper ?? \Drupal::service('entity.helper');
    $this->typedConfigManager = $typed_config_manager ?? \Drupal::service('config.typed');
    if (!empty($configuration) && !empty($plugin_id) && !empty($plugin_definition)) {
      parent::__construct($configuration, $plugin_id, $plugin_definition, $this->entityHelper, $this->typedConfigManager);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

  }

  /**
   * {@inheritDoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
  }

  /**
   * {@inheritDoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {

  }

}
