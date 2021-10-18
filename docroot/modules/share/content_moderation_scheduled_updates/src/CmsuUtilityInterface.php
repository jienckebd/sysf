<?php

namespace Drupal\content_moderation_scheduled_updates;

/**
 * Cmsu utilities.
 */
interface CmsuUtilityInterface {

  /**
   * Get entity reference fields which reference scheduled update entities.
   *
   * @param string $entityTypeId
   *   An entity type ID.
   * @param string $bundle
   *   Bundle for an entity type.
   *
   * @return string[]
   *   An array of field names.
   */
  public function getScheduledUpdateReferenceFields(string $entityTypeId, string $bundle): array;

  /**
   * Get the field which contains new state values for a scheduled update type.
   *
   * @param string $scheduledUpdateTypeId
   *   ID of a scheduled update type entity.
   *
   * @return string|null
   *   The name of the field, or null.
   */
  public function getModerationStateFieldName(string $scheduledUpdateTypeId): ?string;

}
