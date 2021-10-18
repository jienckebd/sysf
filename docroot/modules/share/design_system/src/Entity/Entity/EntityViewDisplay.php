<?php

namespace Drupal\design_system\Entity\Entity;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\design_system\DesignSystem;
use Drupal\design_system\Plugin\Layout\LayoutEntity;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides an entity view display entity that has a layout.
 */
class EntityViewDisplay extends LayoutBuilderEntityViewDisplay {

  use EntityDisplayTrait;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The entity display context.
   *
   * @var \Drupal\design_system\Context\EntityDisplay
   */
  protected $entityDisplayContext;

  /**
   * Whether or not this is in preview mode.
   *
   * @var string
   */
  protected $isPreview;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->designSystem = \Drupal::service('design.system');
    $this->entityDisplayContext = \Drupal::service('design_system.context_provider.entity_display');
  }

  /**
   * Show entity ID instead of label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|mixed|string|null
   *   The entity ID.
   */
  public function label() {
    return $this->id;
  }

  /**
   * @return bool
   */
  public function isFormDisplay() {
    return fnmatch('form__*', $this->getMode());
  }

  /**
   * {@inheritdoc}
   */
  public function setComponent($name, array $options = []) {
    $this->designSystem = \Drupal::service('design.system');

    // Only continue if Layout Builder is enabled.
    if (!$this->isLayoutBuilderEnabled()) {
      return parent::setComponent($name, $options);
    }

    if ($this->isFormDisplay()) {
      $display_context_id = 'form';
    }
    else {
      $display_context_id = 'view';
    }

    if ($display_context_id == 'form') {
      $component_type_field_wrapper = 'entity_field_widget';
    }
    else {
      $component_type_field_wrapper = 'entity_field_formatter';
    }
    $component_block_plugin_id = "component:{$component_type_field_wrapper}:component";

    $target_entity_type_id = $this->getTargetEntityTypeId();
    $bundle_id = $this->getTargetBundle();
    $field_name = $name;

    // Could be empty if previous was extra field block.
    $field_definition = NULL;

    if (!$field_definition = $this->getFieldDefinition($field_name)) {
      if ($display_context_id == 'form') {
        $field_block_plugin_id = "extra_field_form_block:{$target_entity_type_id}:{$bundle_id}:{$field_name}";
      }
      else {
        $field_block_plugin_id = "extra_field_block:{$target_entity_type_id}:{$bundle_id}:{$field_name}";
      }
    }
    else {
      if ($display_context_id == 'form') {
        $field_block_plugin_id = "field_widget:{$target_entity_type_id}:{$bundle_id}:{$field_name}";
      }
      else {
        $field_block_plugin_id = "field_block:{$target_entity_type_id}:{$bundle_id}:{$field_name}";
      }
    }

    $default_section = $this->getDefaultSection();

    $section = $this->getSection(0);
    $region = $section->getDefaultRegion();

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin_instance */
    $block_plugin_instance = \Drupal::service('plugin.manager.block')
      ->createInstance($field_block_plugin_id, []);

    if (!$default_component = $this->designSystem->getDefaultComponent($component_type_field_wrapper)) {
      throw new \Exception("Missing default component for: {$component_type_field_wrapper}");
    }

    $field_block_configuration = [
      'id' => $field_block_plugin_id,
      'label_display' => '0',
      'context_mapping' => [
        'entity' => "@design_system.context_provider.entity_display:display.view.entity:parent:parent:parent",
        'view_mode' => "@design_system.context_provider.entity_display:display.view.mode:parent:parent:parent",
      ],
    ];

    $field_block_configuration += $block_plugin_instance->defaultConfiguration();

    if ($display_context_id == 'view') {
      $field_block_configuration['formatter']['label'] = 'hidden';
    }

    $field_weight = 0;

    // This will skip base field definitions without base field overrides.
    if (!empty($field_definition) && $field_definition instanceof ThirdPartySettingsInterface) {
      $normalized_field_config = $field_definition->getThirdPartySettings('bd');
      $field_weight = isset($normalized_field_config['overview']['weight']) ? $normalized_field_config['overview']['weight'] : 0;
      $entity_field_group = isset($normalized_field_config['overview']['entity_field_group']) ? $normalized_field_config['overview']['entity_field_group'] : NULL;
    }

    $component_wrapper_configuration = [
      'id' => $component_block_plugin_id,
      'component' => $default_component->getRevisionId(),
      'label_display' => '0',
      'context_mapping' => ['entity' => 'layout_builder.entity'],
      'field_override' => [
        'field_block' => [
          0 => [
            'plugin_id' => $field_block_plugin_id,
            'settings' => $field_block_configuration,
          ],
        ],
      ],
    ];

    $new_component = (new SectionComponent(\Drupal::service('uuid')->generate(), $region, $component_wrapper_configuration));

    $new_component->setWeight($field_weight);
    $new_component->setRegion($region);

    $section->appendComponent($new_component);
    return $this;
  }

  /**
   * Gets a default section.
   *
   * @return \Drupal\layout_builder\Section
   *   The default section.
   */
  protected function getDefaultSection() {
    // If no section exists, append a new one.
    if (!$this->hasSection(0)) {
      $this->appendSection(new Section(DesignSystem::DEFAULT_LAYOUT_ID));
    }

    // Return the first section.
    return $this->getSection(0);
  }

  /**
   * Builds the render array for the sections of a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array representing the sections of the entity.
   */
  protected function buildSections(FieldableEntityInterface $entity) {
    return parent::buildSections($entity);
    $contexts = $this->getContextsForEntity($entity);
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3018782.
    $label = new TranslatableMarkup('@entity being viewed', [
      '@entity' => $entity->getEntityType()->getSingularLabel(),
    ]);
    $contexts['layout_builder.entity'] = EntityContext::fromEntity($entity, $label);

    $cacheability = new CacheableMetadata();

    /** @var \Drupal\design_system\Plugin\SectionStorage\DefaultsSectionStorage $storage */
    $storage = \Drupal::service('plugin.manager.layout_builder.section_storage')->findByContext($contexts, $cacheability);

    $build = [];
    if ($storage) {

      $route_object = \Drupal::routeMatch()->getRouteObject();

      // Check if within layout builder temp store.
      if ($section_storage_type = $route_object->getDefault('section_storage_type')) {
        if ($preview_storage = \Drupal::service('layout_builder.tempstore_repository')
          ->get($storage)) {
          $storage = $preview_storage;
        }
      }

      foreach ($storage->getSections() as $delta_layout => $section) {

        $section_layout = $section->getLayout();
        $entity_layout = $section_layout->getLayoutEntity();

        $base_region_names = $section_layout->getPluginDefinition()->getRegionNames();
        $layout_builder_region_component_mapping = [];

        $section_components = $section->getComponents();

        $sublayout_config = [];
        foreach ($section->getComponents() as $layout_builder_component) {

          $component_region_name = $layout_builder_component->getRegion();
          $component_uuid = $layout_builder_component->getUuid();
          $component_config = $layout_builder_component->get('configuration');
          $component_weight = $layout_builder_component->getWeight();

          if (!in_array($component_region_name, $base_region_names)) {

            [$base_region_name, $sublayout_component_uuid, $sublayout_region_name] = explode('__', $component_region_name);

            $sublayout_region_delta = str_replace('row1_col', '', $sublayout_region_name);
            $sublayout_region_delta = $sublayout_region_delta - 1;

            $component_config['base_region_name'] = $base_region_name;
            $component_config['sublayout_region_name'] = $sublayout_region_name;
            $component_config['sublayout_component_uuid'] = $sublayout_component_uuid;

            $sublayout_config[$sublayout_component_uuid]['region'][$sublayout_region_delta][$component_uuid] = $component_config;
          }

        }

        foreach ($section->getComponents() as $layout_builder_component) {

          $component_region_name = $layout_builder_component->getRegion();
          $component_uuid = $layout_builder_component->getUuid();
          $component_config = $layout_builder_component->get('configuration');
          $component_weight = $layout_builder_component->getWeight();

          if ($component_uuid == 'da740467-878d-4ad7-8ef1-ef474ff6a7e2') {
            $d = 1;
          }

          if (empty($component_config['component'])) {
            continue;
          }

          if ($component_config['component'] == 220) {
            $d = 1;
          }

          $entity_component = $this->designSystem->getComponent($component_config['component']);

          $this->recurseAttachLayoutBuilderConfig($entity_component, $component_config);

          $entity_component->layoutBuilderConfig = [
            'layout_delta' => $delta_layout,
            'region_name' => $component_region_name,
            'component_uuid' => $component_uuid,
            'is_root_component' => TRUE,
          ];

          if (!empty($sublayout_config[$component_uuid]['region'])) {
            foreach ($sublayout_config[$component_uuid]['region'] as $sublayout_region_delta => $sublayout_region_components) {

              $field_items_sublayout_regions = $entity_component->get(DesignSystem::FIELD_NAME_REGION);
              $field_items_sublayout_regions_component_values = [];
              //
              //              $mock_entity_region = $this->entityHelper()->getStorage(DesignSystem::ENTITY_TYPE_ID_COMPONENT)->create([
              //                'bundle' => 'region',
              //              ]);
              $mock_entity_region = $field_items_sublayout_regions->get($sublayout_region_delta)->entity;

              $sublayout_delta_component = 0;
              foreach ($sublayout_region_components as $sublayout_region_component_uuid => $sublayout_region_component_config) {
                $sublayout_entity_component = $this->designSystem->getComponent($sublayout_region_component_config['component']);

                $base_region_name = $sublayout_region_component_config['base_region_name'];
                $sublayout_component_uuid = $sublayout_region_component_config['sublayout_component_uuid'];
                $sublayout_region_name = $sublayout_region_component_config['sublayout_region_name'];

                $dynamic_region_name = LayoutEntity::getDynamicRegionId($base_region_name, $sublayout_component_uuid, $sublayout_region_name);

                $mock_entity_region->layoutBuilderConfig = [
                  'layout_delta' => $delta_layout,
                  'region_name' => $dynamic_region_name,
                ];

                $sublayout_entity_component->layoutBuilderConfig = [
                  'layout_delta' => $delta_layout,
                  'region_name' => $dynamic_region_name,
                  'component_uuid' => $sublayout_region_component_uuid,
                  'is_root_component' => TRUE,
                ];

                if (!empty($sublayout_region_component_config['field_override'])) {
                  $sublayout_region_component_config['field_override']['field_block'][0]['settings']['context_mapping']['entity'] = '@design_system.context_provider.entity_display:layout_builder.entity';
                  $sublayout_region_component_config['field_override']['field_block'][0]['settings']['context_mapping']['view_mode'] = '@design_system.context_provider.entity_display:view_mode';
                  foreach ($sublayout_region_component_config['field_override'] as $field_name => $field_overrides) {
                    $sublayout_entity_component->set($field_name, $field_overrides);
                  }
                }

                $field_items_sublayout_regions_component_values[$sublayout_delta_component] = [
                  'entity' => $sublayout_entity_component,
                ];

                $sublayout_delta_component++;
              }

              $mock_entity_region->set(DesignSystem::FIELD_NAME_COMPONENT, $field_items_sublayout_regions_component_values);
              //
              //              $field_items_sublayout_regions->get($sublayout_region_delta)->setValue($field_items_sublayout_regions_component_values);
            }
          }

          if (!empty($component_config['field_override'])) {
            foreach ($component_config['field_override'] as $field_name => $field_overrides) {
              $entity_component->set($field_name, $field_overrides);
            }
          }

          $layout_builder_region_component_mapping[$component_region_name][] = [
            'target_id' => $entity_component->id(),
            'target_revision_id' => $entity_component->getRevisionId(),
            'entity' => $entity_component,
            'weight' => $component_weight,
          ];

        }

        foreach ($entity_layout->get(DesignSystem::FIELD_NAME_REGION) as $delta_region => $field_item) {

          $col = $delta_region + 1;
          $region_name = "row1_col{$col}";
          $entity_region = $field_item->entity;
          $entity_region_component_field_values = [];

          if (!empty($layout_builder_region_component_mapping[$region_name])) {
            uasort($layout_builder_region_component_mapping[$region_name], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
            $entity_region_component_field_values = $layout_builder_region_component_mapping[$region_name];
          }

          $entity_region->set(DesignSystem::FIELD_NAME_COMPONENT, $entity_region_component_field_values);

          $entity_region->layoutBuilderConfig = [
            'layout_delta' => $delta_layout,
            'region_name' => $region_name,
            'is_root_region' => TRUE,
          ];

        }

        $entity_layout->layoutBuilderConfig = [
          'layout_delta' => $delta_layout,
          'is_root_layout' => TRUE,
        ];
        $build[$delta_layout] = $this->designSystem->viewComponent($entity_layout);

      }
    }

    $cacheability->applyTo($build);
    return $build;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_component
   * @param $layout_builder_component_config
   */
  protected function recurseAttachLayoutBuilderConfig(ContentEntityInterface $entity_component, array &$layout_builder_component_config) {

  }

  /**
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   */
  public function presave(EntityStorageInterface $entity_storage) {
    parent::preSave($entity_storage);

    // Remove fields from base display of layout_builder displays to avoid
    // rendering their fields when layout builder overrides them.
    if ($this->getThirdPartySetting('layout_builder', 'enabled')) {
      $this->content = [];

      // Confirm layout overrides field exists on entity.
      if ($this->getThirdPartySetting('layout_builder', 'allow_custom')) {
        $this->addSectionField($this->getTargetEntityTypeId(), $this->getTargetBundle(), OverridesSectionStorage::FIELD_NAME);
      }
    }

  }

}
