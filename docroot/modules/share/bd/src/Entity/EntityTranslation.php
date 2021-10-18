<?php

namespace Drupal\bd\Entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Class EntityTranslation.
 */
class EntityTranslation {
  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;

  /**
   * Translation constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
  }

}
