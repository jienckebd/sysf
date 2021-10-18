<?php

namespace Drupal\design_system;

use Drupal\bd\Php\Arr;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Extends entity display entity type info.
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * EntityOperations constructor.
   *
   * @param \Drupal\design_system\DesignSystem $design_system
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(
    DesignSystem $design_system,
    TranslationInterface $translation,
    EntityHelper $entity_helper,
    EntityTypeBundleInfoInterface $bundle_info,
    AccountInterface $current_user
  ) {
    $this->designSystem = $design_system;
    $this->stringTranslation = $translation;
    $this->entityHelper = $entity_helper;
    $this->bundleInfo = $bundle_info;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('design.system'),
      $container->get('string_translation'),
      $container->get('entity.helper'),
      $container->get('entity_type.bundle.info'),
      $container->get('current_user')
    );
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function entityPresave(EntityInterface $entity) {
    return;
    $entity_type_id = $entity->getEntityTypeId();

    if ($entity_type_id == 'entity_field_group') {
      $target_entity_type_id = $entity->get('entity_type');
      $target_bundle_id = $entity->get('bundle');
      $target_entity_type = $this->entityHelper->getDefinition($target_entity_type_id);
      $target_bundle = $this->entityHelper->getStorage($target_entity_type->getBundleEntityType())->load($target_bundle_id);
      $dependencies = $entity->get('dependencies');
      $config_dependency_name = $target_bundle->getConfigDependencyName();
      if (empty($dependencies['enforced']['config']) || !in_array($config_dependency_name, $dependencies['enforced']['config'])) {
        $dependencies['enforced']['config'][] = $config_dependency_name;
        $entity->set('dependencies', $dependencies);
      }
    }

    if ($entity_type_id == 'entity_view_display') {
      // $entity->setThirdPartySetting('layout_builder', 'enabled', TRUE);
    }

    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function entityInsert(EntityInterface $entity) {
    $this->entityPostSave($entity);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function entityUpdate(EntityInterface $entity) {
    $this->entityPostSave($entity);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function entityPostSave(EntityInterface $entity) {
    return;
    $entity_type_id = $entity->getEntityTypeId();
    $entity_type = $entity->getEntityType();
    $this->processEntityResource($entity);

    if ($entity_type_id == 'entity_form_mode') {

      /** @var \Drupal\design_system\EntityDisplayBuilder $entity_display_builder */
      $entity_display_builder = \Drupal::service('entity_display.builder');
      $entity_display_builder->createViewModeForFormMode($entity);

    }
    elseif ($entity_type_id == 'entity_form_display') {
      $entity->delete();
    }
    elseif ($entity_type->getBundleOf()) {

      $target_entity_type_id = $entity_type->getBundleOf();
      $target_bundle_id = $entity->id();
      $mode_id = "form__default";

      /** @var \Drupal\design_system\EntityDisplayBuilder $entity_display_builder */
      $entity_display_builder = \Drupal::service('entity_display.builder');
      $entity_display_builder->buildDisplayTemplate($target_entity_type_id, $target_bundle_id, 'form', $mode_id, 'default');

    }

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function processEntityResource(EntityInterface $entity) {

    $entity_type_id = $entity->getEntityTypeId();
    $entity_type = $entity->getEntityType();

    if ($resources = $entity_type->get('resource')) {

      if (!empty($resources['per_entity'])) {

        foreach ($resources['per_entity'] as $optional_or_required => $resource_types) {

          $bundle_of_entity_type_id = $entity_type->getBundleOf();

          if (!empty($resource_types['entity_definition'])) {
            foreach ($resource_types['entity_definition'] as $resource_entity_type_id => $entity_definitions_of_entity_type) {

              $entity_type = $this->entityHelper->getDefinition($resource_entity_type_id);
              $entity_storage = $this->entityHelper->getStorage($resource_entity_type_id);
              $id_key = $entity_type->getKey('id');

              foreach ($entity_definitions_of_entity_type as $id => $entity_definition) {

                $variables = [];
                $variables['entity_id'] = $entity->id();
                $variables['bundle_of_entity_type_id'] = $bundle_of_entity_type_id;
                $variables['entity_type_id'] = $entity_type_id;

                if ($resource_entity_type_id == 'base_field_override') {

                  $field_type = \Drupal::service('entity_field.manager')
                    ->getBaseFieldDefinitions($bundle_of_entity_type_id)[$entity_definition['field_name']]->getType();

                  $variables['field_type'] = $field_type;
                }

                Arr::replace($entity_definition, array_keys($variables), array_values($variables));

                if (!$dependent_entity = $entity_storage->load($entity_definition[$id_key])) {
                  $dependent_entity = $entity_storage->create($entity_definition);
                  $dependent_entity_array_original = [];
                }
                else {
                  $dependent_entity_array_original = $dependent_entity->toArray();
                }

                foreach ($entity_definition as $key => $value) {
                  $dependent_entity->set($key, $value);
                }

                $dependent_entity_array = $dependent_entity->toArray();

                if (empty($dependent_entity_array_original) || Arr::recurseDiff($dependent_entity_array_original, $dependent_entity_array)) {
                  $dependent_entity->save();
                  \Drupal::logger('entity')
                    ->notice("Saved entity resource @entity_type_id @entity_id for @parent_entity_type_id @parent_entity_id.", [
                      '@entity_type_id' => $resource_entity_type_id,
                      '@entity_id' => $entity_definition[$id_key],
                      '@parent_entity_type_id' => $entity->getEntityTypeId(),
                      '@parent_entity_id' => $entity->id(),
                    ]);
                }

              }
            }
          }
        }
      }
    }
  }

  /**
   * @param array $operations
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityOperationAlter(array &$operations, EntityInterface $entity) {

    $entity_type_id = $entity->getEntityTypeId();

    if ($entity instanceof FieldableEntityInterface) {
      if ($entity->hasField('layout_builder__layout')) {
        $operations['Layout'] = [
          'title' => t('Layout'),
          'weight' => 100,
          'url' => Url::fromRoute("layout_builder.overrides.{$entity_type_id}.view", [
            $entity_type_id => $entity->id(),
          ]),
        ];

        if (!empty($operations['delete'])) {
          $operations['delete']['weight'] = 110;
        }
      }
    }

    if ($entity_type_id == 'bibcite_reference') {

      $operations['details'] = [
        'title' => t('Details'),
        'weight' => -100,
        'url' => Url::fromRoute("entity.bibcite_reference.canonical", [
          $entity_type_id => $entity->id(),
        ]),
        'localized_options' => [
          'attributes' => [
            'class' => [
              'use-ajax',
            ],
            'data-dialog-type' => 'modal',
            'data-dialog-size' => 'lg',
          ],
        ],
      ];

      $operations['export'] = [
        'title' => t('Export'),
        'weight' => -100,
        'url' => Url::fromRoute("entity.bibcite_reference.edit_form", [
          $entity_type_id => $entity->id(),
        ]),
      ];

      $operations['pubmed'] = [
        'title' => t('View on Pubmed'),
        'weight' => -90,
        'url' => Url::fromRoute("entity.bibcite_reference.edit_form", [
          $entity_type_id => $entity->id(),
        ]),
      ];

      $operations['doi'] = [
        'title' => t('View on DOI'),
        'weight' => -80,
        'url' => Url::fromRoute("entity.bibcite_reference.edit_form", [
          $entity_type_id => $entity->id(),
        ]),
      ];

    }

    $operations['print'] = [
      'title' => t('Print'),
      'weight' => 100,
      'url' => Url::fromRoute("<front>"),
    ];

  }

}
