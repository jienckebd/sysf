<?php

namespace Drupal\design_system\Entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\profile\Entity\Profile as Base;

/**
 * Extends profile in contrib.
 */
class Profile extends Base {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('Set a label for this profile.'))
      ->setDefaultValue(FALSE)
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\ContentEntityBase::label
   */
  public function label() {

    $label = $this->label->value;

    if (!$label) {
      if ($this->isNew()) {
        $label = $this->t('Profile @entity_id', [
          '@entity_id' => $this->id(),
        ]);
      }
      else {
        $label = $this->t('Profile (new)');
      }
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->label->isEmpty()) {
      if ($entity_owner = $this->uid->entity) {
        $this->set('label', $entity_owner->realname->value);
      }
    }
    parent::preSave($storage);
  }

}
