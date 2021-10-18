<?php

namespace Drupal\bd\Plugin\RulesAction;

/**
 * Class Callback.
 *
 * @RulesAction(
 *   id = "callback",
 *   label = @Translation("Callback"),
 *   category = @Translation("System"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("The entity"),
 *       description = @Translation("The entity."),
 *       required = FALSE,
 *     ),
 *     "callback" = @ContextDefinition("string",
 *       label = @Translation("Callback function"),
 *       description = @Translation("The callback function."),
 *       required = FALSE,
 *     ),
 *     "service_id" = @ContextDefinition("string",
 *       label = @Translation("Service ID"),
 *       description = @Translation("The service ID."),
 *       required = FALSE,
 *     ),
 *     "service_method" = @ContextDefinition("string",
 *       label = @Translation("Service method"),
 *       description = @Translation("The service method."),
 *       required = FALSE,
 *     ),
 *   },
 * )
 */
class Callback extends Base {

  /**
   * @param $entity
   * @param $callback
   * @param $service_id
   * @param $service_method
   *
   * @return bool
   */
  protected function doExecute($entity, $callback, $service_id, $service_method) {

    $args = [];

    if (!empty($entity)) {
      $args[] = ["autotheme__{$entity->id()}"];
    }

    if (!empty($service_id) && !empty($service_method)) {
      $service = \Drupal::service($service_id);
      $callable = [$service, $service_method];
    }
    elseif (!empty($callback)) {
      $callable = $callback;
    }
    else {
      throw new \Exception("Invalid callback.");
    }

    call_user_func_array($callable, $args);

    return TRUE;
  }

}
