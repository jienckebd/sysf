<?php

namespace Drupal\bd\Handler;

use Drupal\bd\Entity\EntityRelation;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class EntityCrud.
 */
class EntityCrud implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * EntityCrud constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   */
  public function __construct(
    EntityHelper $entity_helper
  ) {
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.helper')
    );
  }

  /**
   * @param array $entities
   * @param $entity_type_id
   */
  public function entityLoad(array $entities, $entity_type_id) {
    $d = 1;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityPresave(EntityInterface $entity) {

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    if ($entity->hasField('description')) {
      if (!$description = $entity->description->value) {
        $entity->set('description', ' ');
      }
    }

    if (!empty($entity->duplicateSource)) {

      if ($entity->getEntityTypeId() == 'block_content') {

      }

      foreach ($entity->getFieldDefinitions() as $field_name => $field_defintiion) {
        if ($field_defintiion->getType() !== 'layout_section') {
          continue;
        }


      }

    }

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityInsert(EntityInterface $entity) {
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityUpdate(EntityInterface $entity) {

    /** @var \Drupal\bd\Entity\EntityRelation $entity_relation */
    $entity_relation = \Drupal::service('entity.relation');
    if ($entity_dependencies = $entity_relation->getRelationsTargetByEntity($entity, 'dependency', ['field_type' => EntityRelation::DEPENDENCY_TYPE_RESAVE_REFERENCING])) {
      foreach ($entity_dependencies as $entity) {
        $entity->save();
      }
    }

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityPreDelete(EntityInterface $entity) {

    $entity_type_id = $entity->getEntityTypeId();
    if ($entity_type_id == 'theme_entity') {
      $theme_handler = \Drupal::service('theme_handler');
      $theme_handler->processThemeDelete($entity);
    }

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityDelete(EntityInterface $entity) {

    if ($entity instanceof ConfigEntityInterface) {

      // If this is a config entity, delete its wrapper if it exists.
      /** @var \Drupal\bd\Config\Wrapper\Manager $manager */
      $manager = \Drupal::service('config_entity_wrapper.manager');
      if ($config_entity_wrapper = $manager->getWrapperForEntity($entity)) {
        $config_entity_wrapper->thirdPartySettingsIsSubjectDelete = TRUE;
        $config_entity_wrapper->delete();
      }
    }
    elseif ($entity->getEntityTypeId() == 'config_entity_wrapper' && empty($entity->thirdPartySettingsIsSubjectDelete)) {

      // If this is a config entity wrapper, delete its subject.
      $subject_entity_type_id = $entity->get('subject')->target_type;
      $subject_entity_id = $entity->get('subject')->target_id;

      if (!empty($subject_entity_type_id) && !empty($subject_entity_id) && $entity_subject = \Drupal::service('entity.helper')->getStorage($subject_entity_type_id)->load($subject_entity_id)) {
        $entity_subject->delete();
      }
      else {
        \Drupal::logger('default')->warning("Config entity wrapper subject does not exist: @entity_type_id / @entity_id", [
          '@entity_type_id' => $subject_entity_type_id,
          '@entity_id' => $subject_entity_id,
        ]);
      }

    }

  }

}
