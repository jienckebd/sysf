<?php

namespace Drupal\bd\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines an interface for Extra Field Display plugins.
 */
interface PluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Get options for a given entity reference widget.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_items
   *   The field items.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The subject entity.
   *
   * @return array
   *   The options.
   */
  public function getOption(FieldItemListInterface $field_items, EntityInterface $entity);

  /**
   * Allow deriver plugin to modify field formatter output.
   *
   * @param array $build
   *   The current field formatter build.
   * @param array $formatter_settings
   *   The field formatter settings.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param string $langcode
   *   The langcode.
   *
   * @return mixed
   */
  public function viewElements(array &$build, array $formatter_settings, FieldItemListInterface $items, $langcode);

}
