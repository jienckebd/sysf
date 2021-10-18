<?php

namespace Drupal\bd\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;

/**
 * Build entity dependencies.
 *
 * @RulesAction(
 *   id = "entity_dependency_build",
 *   label = @Translation("Entity dependency: build"),
 *   category = @Translation("Entity"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity."),
 *       assignment_restriction = "selector"
 *     ),
 *     "dependency_type" = @ContextDefinition("string",
 *       label = @Translation("Dependency type"),
 *       description = @Translation("The dependency type."),
 *       options_provider = "\Drupal\bd\Plugin\OptionsProvider\DependencyType",
 *       assignment_restriction = "input",
 *       required = TRUE,
 *     ),
 *     "entity_type" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *       description = @Translation("The entity type."),
 *       options_provider = "\Drupal\rules\Plugin\OptionsProvider\EntityTypeOptions",
 *       assignment_restriction = "input",
 *       required = FALSE,
 *     ),
 *     "bundle" = @ContextDefinition("string",
 *       label = @Translation("Bundle"),
 *       description = @Translation("The bundle."),
 *       options_provider = "\Drupal\rules\Plugin\OptionsProvider\EntityBundleOptions",
 *       assignment_restriction = "input",
 *       required = FALSE,
 *     ),
 *   }
 * )
 */
class EntityDependencyBuild extends Base {

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $dependency_type
   * @param $entity_type
   * @param $bundle
   */
  protected function doExecute(EntityInterface $entity, $dependency_type, $entity_type, $bundle) {

    /** @var \Drupal\bd\Entity\EntityRelation $entity_relation */
    $entity_relation = \Drupal::service('entity.relation');

    $entity_relation->buildDependencyReferencedEntities($entity, [$entity_type], [$bundle], $dependency_type);

  }

}
