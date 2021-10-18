<?php

namespace Drupal\bd\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityTypeApplyUpdate.
 *
 * @BusinessRulesAction(
 *   id = "cache_purge",
 *   label = @Translation("Cache: Purge"),
 *   group = @Translation("Cache"),
 *   description = @Translation("Purge a given cache."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CachePurge extends Base {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings = [];

    $settings['cache_id'] = [
      '#title' => $this->t('Cache ID'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options_provider' => 'plugin.type',
      '#default_value' => $item->getSettings('cache_id'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $cache_id = $action->getSettings('cache_id');

    try {
      $service = \Drupal::service($cache_id);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $result = $service->clearCachedDefinitions();

    return $result;
  }

}
