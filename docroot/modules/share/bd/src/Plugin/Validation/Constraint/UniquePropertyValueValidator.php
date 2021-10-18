<?php

namespace Drupal\bd\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueFieldValue constraint.
 *
 * This checks for both content entities and config entities.
 */
class UniquePropertyValueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {

    $typed_data_instance = $this->context->getObject();
    if (!$root = $typed_data_instance->getRoot()) {
      return;
    }

    if (!$root_value = $root->getValue()) {
      return;
    }

    if (!$root_value instanceof EntityInterface) {
      return;
    }

    $entity = $root_value;
    $property_name = $typed_data_instance->getName();

    $entity_type_id = $entity->getEntityTypeId();
    $entity_label = $entity->label();

    $entity_helper = \Drupal::service('entity.helper');

    $entity_storage = $entity_helper
      ->getStorage($entity_type_id);

    $entity_definition = $entity_helper
      ->getDefinition($entity_type_id);

    $load_properties = [
      $property_name => $value,
    ];

    $entities_with_value = $entity_storage->loadByProperties($load_properties);

    if (!empty($entities_with_value)) {

      if (count($entities_with_value) == 1) {
        $entity_with_value = reset($entities_with_value);
        // The discovered entity is this entity. Allow it.
        if ($entity_with_value->uuid() == $entity->uuid()) {
          return;
        }
      }

      $this->context->addViolation($constraint->notUnique, [
        '%value' => $entity_label,
        '%property_name' => $property_name,
        '%entity_type_label' => $entity_definition->getSingularLabel(),
      ]);
    }

  }

}
