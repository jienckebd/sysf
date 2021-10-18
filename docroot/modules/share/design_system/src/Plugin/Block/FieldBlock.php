<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\layout_builder\Plugin\Block\FieldBlock as Base;

/**
 * Extends layout_builder field block.
 *
 * @Block(
 *   id = "field_block",
 *   deriver = "\Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver",
 * )
 */
class FieldBlock extends Base {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    if ($this->fieldDefinition instanceof ThirdPartySettingsInterface) {
      return parent::defaultConfiguration();
    }

    return [
      'label_display' => FALSE,
      'formatter' => [
        'label' => 'above',
        'type' => $this->pluginDefinition['default_formatter'],
        'settings' => [],
        'third_party_settings' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->getEntity()->bundle() == 'entity_field_widget') {
      $d = 1;
    }
    if ($this->fieldName == 'field_cmp_region') {
      $d = 1;
    }
    $build = parent::build();
    return $build;
  }

}
