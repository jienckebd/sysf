<?php

namespace Drupal\design_system\Plugin\Derivative;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\design_system\DesignSystem;
use Drupal\ui_patterns\Plugin\Deriver\AbstractPatternsDeriver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Class PatternLabDeriver.
 *
 * @package Drupal\ui_patterns_pattern_lab\Deriver
 */
class UiPatternComponentType extends AbstractPatternsDeriver {

  /**
   * Typed data manager service.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * UiPatternComponentType constructor.
   *
   * @param $base_plugin_id
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\design_system\DesignSystem $design_system
   */
  public function __construct(
    $base_plugin_id,
    TypedDataManager $typed_data_manager,
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    DesignSystem $design_system
  ) {
    parent::__construct($base_plugin_id, $typed_data_manager);
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->designSystem = $design_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('typed_data_manager'),
      $container->get('entity.helper'),
      $container->get('entity_field.manager'),
      $container->get('design.system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    $patterns = [];

    $component_types = $this->entityHelper
      ->getStorage(DesignSystem::ENTITY_TYPE_ID_COMPONENT_TYPE)
      ->loadMultiple();

    if (empty($component_types)) {
      return $patterns;
    }

    $path_module = drupal_get_path('module', 'design_system');
    $twig_file_path = "{$path_module}/templates/entity.html.twig";

    foreach ($component_types as $component_type_id => $component_type) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $component_type */

      $definition = [];
      $pattern_id = "component__{$component_type_id}";

      // Set pattern meta.
      // Convert hyphens to underscores so that the pattern id will validate.
      // Also strip initial numbers that are ignored by Pattern Lab when naming.
      $definition['id'] = $pattern_id;

      $definition['provider'] = 'design_system';

      // Set other pattern values.
      // The label is typically displayed in any UI navigation items that
      // refer to the component. Defaults to a title-cased version of the
      // component name if not specified.
      $definition['label'] = $component_type->label();
      $definition['description'] = 'todo';
      $definition['fields'] = $this->getFields($component_type);
      $definition['libraries'] = [];

      // Override patterns behavior.
      // Use a stand-alone Twig file as template.
      $definition["use"] = $twig_file_path;
      $definition["base path"] = dirname($twig_file_path);
      $definition["file name"] = $twig_file_path;

      // Add pattern to collection.
      $patterns[] = $this->getPatternDefinition($definition);

    }
    return $patterns;
  }

  /**
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $component_type
   *
   * @return array
   */
  private function getFields(ConfigEntityInterface $component_type) {

    // The field data to pass to the template when rendering previews.
    $fields = [];

    $field_definitions = $this->entityFieldManager->getFieldDefinitions(DesignSystem::ENTITY_TYPE_ID_COMPONENT, $component_type->id());

    foreach ($field_definitions as $field_name => $field_definition) {

      if ($field_definition instanceof BaseFieldDefinition) {
        continue;
      }

      $fields[$field_name] = [
        "type" => NULL,
        "label" => $field_definition->getLabel(),
        "description" => 'todo',
        "preview" => 'todo',
      ];
    }

    return $fields;
  }

  /**
   *
   */
  public function oldFields() {

    // If we've explicitly defined ui_pattern_definitions, parse fields from there.
    if (isset($content['ui_pattern_definition']['fields'])) {
      foreach ($content['ui_pattern_definition']['fields'] as $field => $definition) {
        $fields[$field] = [
          "type" => isset($definition['type']) ? $definition['type'] : NULL,
          "label" => isset($definition['label']) ? $definition['label'] : '',
          "description" => isset($definition['description']) ? $definition['description'] : NULL,
          "preview" => isset($definition['preview']) ? $definition['preview'] : NULL,
        ];
      }
    }
    // Otherwise, cross our fingers and use the fields in the definition file.
    else {
      foreach ($content as $field => $preview) {
        // Ignore the ui_pattern_definiton key if we're using it to define other
        // aspects of the pattern.
        if ($field != 'ui_pattern_definition') {
          $fields[$field] = [
            "label" => ucwords($field),
            "preview" => $preview,
          ];
        }

        if (is_array($preview) && in_array("include()", array_keys($preview)) && isset($preview["include()"])) {
          $fields[$field]["type"] = "render";
          $fields[$field]["preview"] = $this->includePatternFiles($preview["include()"]);
          $fields[$field]["description"] = t('Rendering of a @pattern pattern.',
            ['@pattern' => $preview["include()"]["pattern"]]);
        }

        if (is_array($preview) && in_array("join()", array_keys($preview)) && isset($preview["join()"])) {
          $fields[$field]["type"] = "render";
          $fields[$field]["preview"] = $this->joinTextValues($preview["join()"]);
          $fields[$field]["description"] = t('Rendering of a pattern joining text values.');
        }

        if (is_array($preview) && in_array("Attribute()", array_keys($preview)) && isset($preview["Attribute()"])) {
          $fields[$field]["type"] = "Attribute";
          $fields[$field]["preview"][] = $this->createAttributeObjects($preview["Attribute()"]);
        }

        if (is_array($preview) && in_array("Url()", array_keys($preview)) && isset($preview["Url()"])) {
          $fields[$field]["type"] = "Url";
          $fields[$field]["preview"][] = $this->createUrlObjects($preview["Url()"]);
        }

      }
    }

    // Remove illegal attributes field.
    unset($fields['attributes']);
  }

}
