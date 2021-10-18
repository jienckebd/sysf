<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList;
use Drupal\slick\Plugin\Field\FieldFormatter\SlickEntityReferenceFormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceFormatterTrait;
use Drupal\slick\Plugin\Field\FieldFormatter\SlickFormatterTrait;

/**
 * Plugin implementation of the 'slick media' formatter.
 *
 * @FieldFormatter(
 *   id = "slick_media",
 *   label = @Translation("Slick carousel"),
 *   description = @Translation("Display the referenced entities as a Slick carousel."),
 *   field_types = {
 *     "entity_reference",
 *     "dynamic_entity_reference",
 *     "entity_reference_revisions",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SlickEntityFormatter extends SlickEntityReferenceFormatterBase {

  use SlickFormatterTrait;
  use DynamicEntityReferenceFormatterTrait {
    prepareView as dynamicEntityReferencePrepareView;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {

    if (!empty($entities_items[0]) && ($entities_items[0] instanceof DynamicEntityReferenceFieldItemList)) {
      return $this->dynamicEntityReferencePrepareView($entities_items);
    }

    return parent::prepareView($entities_items);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    $this->setSetting('view_mode', 'teaser');

    // Collects specific settings to this formatter.
    $settings = $this->buildSettings();
    $build = ['settings' => $settings];

    // Modifies settings before building elements.
    $this->formatter->preBuildElements($build, $items, $entities);

    // Build the elements.
    $this->buildElements($build, $entities, $langcode);

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $items, $entities);

    return $this->manager()->build($build);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple();
  }

}
