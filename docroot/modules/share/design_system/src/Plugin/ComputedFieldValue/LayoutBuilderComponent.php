<?php

namespace Drupal\design_system\Plugin\ComputedFieldValue;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\bd\Plugin\EntityPluginBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\design_system\DesignSystem;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides computed field values based on related entity values.
 *
 * @ComputedFieldValue(
 *   plugin_type = "computed_field_value",
 *   id = "layout_builder_component",
 *   label = @Translation("Layout builder component"),
 *   description = @Translation("Derives field values for child layout builder
 *   components."),
 * )
 */
class LayoutBuilderComponent extends EntityPluginBase {

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * LayoutBuilderComponent constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   * @param \Drupal\design_system\DesignSystem $design_system
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityHelper $entity_helper,
    TypedConfigManagerInterface $typed_config_manager,
    DesignSystem $design_system
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_helper, $typed_config_manager);
    $this->designSystem = $design_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.helper'),
      $container->get('config.typed'),
      $container->get('design.system')
    );
  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getComputedValue(FieldableEntityInterface $entity, FieldDefinitionInterface $field_definition) {
    $items = [];

    if (!$display_context = $this->designSystem->getDisplayContext('view')) {
      return $items;
    }

    if (empty($display_context['display'])) {
      return $items;
    }

    $entity_subject = $display_context['entity'];
    $mode_id = $display_context['mode'];
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $entity_display */
    $entity_display = $display_context['display'];

    if (!$layout_builder_settings = $entity_display->getThirdPartySettings('layout_builder')) {
      return $items;
    }

    if (empty($layout_builder_settings['sections'])) {
      return $items;
    }

    $entity_id = $entity->id();
    $entity_layout_builder_component_uuid = NULL;
    $entity_storage_component = $this->entityHelper->getStorage(DesignSystem::ENTITY_TYPE_ID_COMPONENT);

    /**
     * @var int $delta
     * @var \Drupal\layout_builder\Section $section
     */
    foreach ($layout_builder_settings['sections'] as $delta => $section) {

      foreach ($section->getComponents() as $uuid => $component) {

        $component_config = $component->get('configuration');
        if (!empty($component_config['component']) && $component_config['component'] == $entity_id) {
          $entity_layout_builder_component_uuid = $uuid;
          break;
        }

      }

    }

    if (empty($entity_layout_builder_component_uuid)) {
      return $items;
    }

    foreach ($layout_builder_settings['sections'] as $delta => $section) {

      foreach ($section->getComponents() as $uuid => $component) {

        $component_config = $component->get('configuration');

        if (empty($component_config['component'])) {
          continue;
        }

        $region_id = $component->getRegion();

        if (substr_count($region_id, '__') != 2) {
          continue;
        }

        [$base_region_id, $component_string, $component_uuid] = explode('__', $region_id);

        if ($entity_layout_builder_component_uuid == $component_uuid) {

          $child_component = $this->designSystem->getComponent($component_config['component']);

          if (!empty($component_config['field_override'])) {
            $child_component = $child_component->createDuplicate();
            $child_component->disableSave = TRUE;
            foreach ($component_config['field_override'] as $override_field_name => $override_field_values) {
              $child_component->set($override_field_name, $override_field_values);
            }
          }

          $child_component->set('uuid', $uuid);

          $items[] = [
            'target_id' => $child_component->id(),
            'target_revision_id' => $child_component->getRevisionId(),
            'entity' => $child_component,
            'weight' => $component->getWeight(),
          ];

        }

        // Apply overrides.
      }

    }

    uasort($items, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $return = [];
    $delta = 0;
    foreach ($items as $original_delta => $item) {
      $return[$delta] = $item;
      $delta++;
    }
    unset($items);

    return $return;
  }

}
