<?php

namespace Drupal\autoref\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Save entity' action.
 *
 * @RulesAction(
 *   id = "autoref_set",
 *   label = @Translation("Autoreference: Set"),
 *   category = @Translation("Entity"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be saved permanently.")
 *     ),
 *     "matcher" = @ContextDefinition("string",
 *       label = @Translation("Matcher Plugin"),
 *       description = @Translation("Define the matcher plugins that will be used during auto referencing."),
 *       default_value = "string_match",
 *       required = FALSE,
 *     ),
 *     "chained_entity_depth" = @ContextDefinition("integer",
 *       label = @Translation("Chained Entity Depth"),
 *       description = @Translation("Specify the number of chained entities that will be searched during matching. Enter 0 for no chained entities."),
 *       default_value = 0,
 *       required = FALSE,
 *     ),
 *     "cron_queue" = @ContextDefinition("boolean",
 *       label = @Translation("Cron Queue"),
 *       description = @Translation("Queue auto reference calculations and process on cron."),
 *       default_value = FALSE,
 *       required = FALSE
 *     )
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 */
class AutorefSet extends RulesActionBase {

  /**
   * Flag that indicates if the entity should be auto-saved later.
   *
   * @var bool
   */
  protected $saveLater = TRUE;

  /**
   * Saves the Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be saved.
   * @param string $matcher
   *   The matcher plugins.
   * @param int $chained_entity_depth
   *   The number of entities to search in depth.
   */
  protected function doExecute(EntityInterface $entity, $matcher = NULL, $chained_entity_depth = NULL, $cron_queue = NULL) {

    $matcher_plugins = [];
    if (!empty($matcher)) {
      $matcher_plugins[] = $matcher;
    }

    // @todo allow ordering the matcher plugins.
    $matcher_plugins = [
      'string_match',
      'common_entity',
    ];

    \Drupal::service('autoref.manager')->processEntity($entity, $matcher_plugins, $chained_entity_depth);
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    return [];
  }

}
