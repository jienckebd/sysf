<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\layout_builder\Plugin\Block\ExtraFieldBlock as Base;

/**
 * Extends layout_builder extra field block.
 *
 * @Block(
 *   id = "extra_field_block",
 *   deriver = "\Drupal\layout_builder\Plugin\Derivative\ExtraFieldBlockDeriver",
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class ExtraFieldBlock extends Base {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [
      '#tree' => TRUE,
    ];

    return $build;
  }

}
