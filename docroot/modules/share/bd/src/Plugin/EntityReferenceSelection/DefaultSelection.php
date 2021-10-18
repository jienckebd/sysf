<?php

namespace Drupal\bd\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection as Base;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Extends core selection handler.
 */
class DefaultSelection extends Base {

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager = NULL, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository = NULL) {

    if (empty($configuration['sort']['field'])) {
      $entity_type = $entity_type_manager->getDefinition($configuration['target_type']);

      if ($entity_key_label = $entity_type->getKey('label')) {
        $configuration['sort']['field'] = $entity_key_label;
      }
      else {
        $configuration['sort']['field'] = $entity_type->getKey('id');
      }

      if (empty($configuration['sort']['direction'])) {
        $configuration['sort']['direction'] = 'ASC';
      }

    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles']['#required'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {

    $entity_query = parent::buildEntityQuery($match, $match_operator);

    $config = $this->getConfiguration();

    if (!empty($config['property_match'])) {
      foreach ($config['property_match'] as $key => $value) {
        $entity_query->condition($key, $value);
      }
    }

    return $entity_query;
  }

}
