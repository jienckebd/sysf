<?php

namespace Drupal\design_system;

use Drupal\design_system\Plugin\Layout\LayoutEntity;
use Drupal\layout_builder\LayoutTempstoreRepository as Base;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Extends layout builder temp store.
 */
class LayoutTempstoreRepository extends Base {

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * LayoutTempstoreRepository constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   * @param \Drupal\design_system\DesignSystem $design_system
   */
  public function __construct(
    SharedTempStoreFactory $temp_store_factory,
    DesignSystem $design_system
  ) {
    parent::__construct($temp_store_factory);
    $this->designSystem = $design_system;
  }

  /**
   * {@inheritdoc}
   */
  public function set(SectionStorageInterface $section_storage) {

    foreach ($section_storage->getSections() as $section) {

      $layout_settings = $section->getLayoutSettings();
      if (!empty($layout_settings['sublayout'])) {
        unset($layout_settings['sublayout']);
      }

      $layout_plugin_definition = $section->getLayout();
      if (!$layout_plugin_definition instanceof LayoutEntity) {
        continue;
      }
      /** @var \Drupal\design_system\Plugin\Layout\LayoutEntity $layout_plugin_definition */

      $section_components = $section->getComponents();

      foreach ($section_components as $component) {

        $config = $component->get('configuration');
        if (empty($config['component'])) {
          continue;
        }

        $layout_component_uuid = $component->getUuid();
        $layout_component_region = $component->getRegion();

        $entity_component = $this->designSystem->getComponent($config['component']);
        if ($entity_component->hasField(DesignSystem::FIELD_NAME_REGION)) {

          $region_config = [];

          $col = 1;
          foreach ($entity_component->get(DesignSystem::FIELD_NAME_REGION) as $delta => $field_item_region) {

            $entity_region = $field_item_region->entity;

            $region = [];
            $sublayout_region_id = "row1_col{$col}";
            $region['label'] = $entity_region->label();
            $region_config[$sublayout_region_id] = $region;

            $col++;
          }

          $layout_settings['sublayout'][$layout_component_region][$layout_component_uuid] = $region_config;

        }

      }

      $section->setLayoutSettings($layout_settings);

    }

    parent::set($section_storage);
  }

}
