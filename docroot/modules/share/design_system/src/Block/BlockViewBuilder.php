<?php

namespace Drupal\design_system\Block;

use Drupal\block\BlockViewBuilder as Base;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element;

/**
 * Class BlockViewBuilder.
 *
 * @package Drupal\design_system\Block
 */
class BlockViewBuilder extends Base {

  /**
   * {@inheritdoc}
   */
  public static function preRender($build) {
    $content = $build['#block']->getPlugin()->build();

    // @todo workaround to make empty blocks go through preprocessing.
    if (Element::isEmpty($content)) {
      $content['#markup'] = '';
    }
    // @todo move all block config processing here.
    // Remove the block entity from the render array, to ensure that blocks
    // can be rendered without the block config entity.
    unset($build['#block']);
    if ($content !== NULL && !Element::isEmpty($content)) {
      $build['content'] = $content;
    }
    // Either the block's content is completely empty, or it consists only of
    // cacheability metadata.
    else {
      // Abort rendering: render as the empty string and ensure this block is
      // render cached, so we can avoid the work of having to repeatedly
      // determine whether the block is empty. For instance, modifying or adding
      // entities could cause the block to no longer be empty.
      $build = [
        '#markup' => '',
        '#cache' => $build['#cache'],
      ];

      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    return $build;
  }

}
