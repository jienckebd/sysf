<?php

namespace Drupal\design_system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Provides a deriver around entity type bundles.
 */
class EntityTypeBundle extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs new EntityTypeBundle.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityTypeBundleInfoInterface $entity_type_bundle_info
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.helper'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    if (empty($base_plugin_definition['entity_type'])) {
      return $this->derivatives;
    }

    $entity_type_id = $base_plugin_definition['entity_type'];
    if (!$entity_type_bundle_info_block = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id)) {
      return $this->derivatives;
    }

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    if (!$entity_type_id_bundle = $entity_type->getBundleEntityType()) {
      return $this->derivatives;
    }

    $entity_storage_bundle = $this->entityHelper->getStorage($entity_type_id_bundle);

    foreach ($this->entityHelper->getDefinitions() as $entity_type_id_context => $entity_type_context) {

      if (!$entity_type_context instanceof ContentEntityTypeInterface) {
        continue;
      }

      foreach ($entity_type_bundle_info_block as $bundle_id => $bundle_info) {

        if (!$entity_bundle = $entity_storage_bundle->load($bundle_id)) {
          continue;
        }

        $derivative_id = $bundle_id . PluginBase::DERIVATIVE_SEPARATOR . $entity_type_id_context;
        $derivative = $base_plugin_definition;

        $derivative['admin_label'] = $entity_bundle->label();

        $context_definition = EntityContextDefinition::fromEntityTypeId($entity_type_id_context)
          ->setLabel($entity_type_context->getLabel());

        $derivative['context_definitions'] = [
          'entity' => $context_definition,
        ];

        $derivative['bundle'] = $bundle_id;

        $this->derivatives[$derivative_id] = $derivative;

      }
    }
    return $this->derivatives;
  }

}
