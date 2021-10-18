<?php

namespace Drupal\bd\Entity\ListBuilder;

use Drupal\Core\Entity\EntityListBuilder as Base;
use Drupal\Core\Entity\EntityInterface;

/**
 * Extends core entity list builder.
 */
class EntityListBuilder extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();

    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('ID');

    if ($this->entityType->hasKey('description')) {
      $header['description'] = $this->t('Description');
    }

    // If operations set, move to end. Header needs to move as well as row.
    if (!empty($header['operations'])) {
      $operations = array_shift($header);
      $header['operations'] = $operations;
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    $row['label']['data'] = $entity->label();
    $row['id']['data'] = $entity->id();

    if ($this->entityType->hasKey('description')) {
      $entity_key_description = $this->entityType->getKey('description');
      $row['description']['data'] = $entity->get($entity_key_description);
    }

    // If operations set, move to end.
    if (!empty($row['operations'])) {
      $operations = array_shift($row);
      $row['operations'] = $operations;
    }

    return $row;
  }

}
