<?php

namespace Drupal\bd\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 *
 */
trait ComputedFieldValuePluginTrait {

  use ComputedItemListTrait;

  /**
   *
   */
  public function computeValue() {

    $plugin_id = $this->getSetting('plugin_id');
    $plugin_config = $this->getSetting('plugin_config') ?: [];

    /** @var \Drupal\bd\PluginManager\EntityPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.computed_field_value');
    $plugin = $plugin_manager->createInstance($plugin_id, $plugin_config);

    $entity = $this->getEntity();
    $field_definition = $this->getFieldDefinition();

    $result = $plugin->getComputedValue($entity, $field_definition);

    if (!empty($result)) {
      foreach ($result as $delta => $field_item) {
        if ($field_item instanceof FieldItemInterface) {
          $field_item = $field_item->getValue();
        }
        $this->list[] = $this->createItem($delta, $field_item);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->computeValue();
    return parent::getValue();
  }

}
