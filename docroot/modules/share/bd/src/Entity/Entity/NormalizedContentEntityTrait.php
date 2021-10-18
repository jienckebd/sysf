<?php

namespace Drupal\bd\Entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\user\UserInterface;

/**
 * Trait to be injected in to a content entity class.
 */
trait NormalizedContentEntityTrait {

  use EntityPublishedTrait;
  use EntityChangedTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {

    // @workaround because theme entity threw exception in ContentEntityBase.
    if ($this->validationRequired && !$this->validated) {
      parent::validate();
    }

    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the node owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }

    $default_field_value = [
      'weight' => 0,
      'sticky' => 0,
      'promote' => 0,
    ];

    foreach ($default_field_value as $field_name => $field_value) {
      if ($this->hasField($field_name) && $this->get($field_name)->isEmpty()) {
        $this->set($field_name, $field_value);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $nodes) {
    parent::postDelete($storage, $nodes);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    // This override exists to set the operation to the default value "view".
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPromoted() {
    return (bool) $this->get('promote')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPromoted($promoted) {
    $this->set('promote', $promoted ? ContentInterface::PROMOTED : ContentInterface::NOT_PROMOTED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSticky() {
    return (bool) $this->get('sticky')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSticky($sticky) {
    $this->set('sticky', $sticky ? ContentInterface::STICKY : ContentInterface::NOT_STICKY);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->getRevisionUser();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->setRevisionUserId($uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int
   *   The current user ID, defaulting to 1.
   */
  public static function getCurrentUserId() {
    $uid = \Drupal::currentUser()->id();
    if ($uid == 0) {
      $uid = 1;
    }
    return $uid;
  }

}
