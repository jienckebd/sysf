<?php

namespace Drupal\content_moderation_scheduled_updates;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\bd\Entity\EntityHelper;

/**
 * Cmsu utilities.
 */
class CmsuUtility implements CmsuUtilityInterface {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Contains a map of scheduled update types which change moderation_state.
   *
   * Keys contain scheduled update type ID, values are the name of the field
   * on the scheduled update entity containing new state values. If value is
   * null then the type does not map to content moderation field.
   *
   * @var array
   */
  protected $moderationStateFieldMap = [];

  /**
   * Creates a new CmsuHooks instance.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entityHelper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(EntityHelper $entityHelper, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityHelper = $entityHelper;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheduledUpdateReferenceFields(string $entityTypeId, string $bundle): array {
    $definitions = $this->entityFieldManager
      ->getFieldDefinitions($entityTypeId, $bundle);

    $fieldNames = [];
    foreach ($definitions as $definition) {
      if ('entity_reference' !== $definition->getType()) {
        continue;
      }

      if ('scheduled_update' !== $definition->getFieldStorageDefinition()->getSetting('target_type')) {
        continue;
      }

      $fieldNames[] = $definition->getName();
    }

    return $fieldNames;
  }

  /**
   * {@inheritdoc}
   */
  public function getModerationStateFieldName(string $scheduledUpdateTypeId): ?string {
    if (array_key_exists($scheduledUpdateTypeId, $this->moderationStateFieldMap)) {
      return $this->moderationStateFieldMap[$scheduledUpdateTypeId];
    }

    /** @var \Drupal\scheduled_updates\ScheduledUpdateTypeInterface|null $scheduledUpdateType */
    $scheduledUpdateType = $this->entityHelper
      ->getStorage('scheduled_update_type')
      ->load($scheduledUpdateTypeId);

    $fieldName = array_search('moderation_state', $scheduledUpdateType->getFieldMap());
    $fieldName = $fieldName ? $fieldName : NULL;
    $this->moderationStateFieldMap[$scheduledUpdateTypeId] = $fieldName;

    return $this->moderationStateFieldMap[$scheduledUpdateTypeId];
  }

}
