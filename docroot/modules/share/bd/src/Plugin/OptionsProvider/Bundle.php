<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for entity type bundles.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "bundle",
 *   label = @Translation("Bundles"),
 *   description = @Translation("Bundles of an entity type."),
 * )
 */
class Bundle extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info */
    $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');

    $option = [];

    $entity_type_id = $this->configuration['entity_type'];

    foreach ($entity_type_bundle_info->getBundleInfo($entity_type_id) as $bundle_id => $bundle_data) {
      $option[$bundle_id] = $bundle_data['label'];
    }

    return $option;

  }

}
