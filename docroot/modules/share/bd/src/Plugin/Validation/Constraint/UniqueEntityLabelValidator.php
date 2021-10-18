<?php

namespace Drupal\bd\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueFieldValue constraint.
 *
 * This checks for both content entities and config entities.
 */
class UniqueEntityLabelValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    $entity_helper = \Drupal::service('entity.helper');

    $entity_id = $entity->id();
    $entity_label = $entity->label();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_bundle_id = $entity->bundle();

    $entity_storage = $entity_helper
      ->getStorage($entity_type_id);

    $entity_definition = $entity_helper
      ->getDefinition($entity_type_id);

    $entity_label_key = $entity_definition->getKey('label');
    $entity_bundle_key = $entity_definition->getKey('bundle');

    $load_properties = [
      $entity_label_key => $entity_label,
    ];

    if (!empty($entity_bundle_key) && !empty($entity_bundle_id)) {
      $load_properties[$entity_bundle_key] = $entity_bundle_id;
    }

    $entities_with_label = $entity_storage->loadByProperties($load_properties);

    if (!empty($entities_with_label)) {

      if (count($entities_with_label) == 1) {
        $entity_with_label = reset($entities_with_label);
        // The discovered entity is this entity. Allow it.
        if ($entity_with_label->uuid() == $entity->uuid()) {
          return;
        }
      }

      $this->context->addViolation($constraint->notUnique, [
        '%value' => $entity_label,
        '%entity_type_label' => $entity_definition->getSingularLabel(),
      ]);
    }

  }

}
