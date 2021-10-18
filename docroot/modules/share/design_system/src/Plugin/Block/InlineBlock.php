<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\layout_builder\Plugin\Block\InlineBlock as Base;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an inline block plugin type.
 *
 * @Block(
 *  id = "inline_block",
 *  admin_label = @Translation("Inline block"),
 *  category = @Translation("Inline blocks"),
 *  deriver = "Drupal\layout_builder\Plugin\Derivative\InlineBlockDeriver",
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class InlineBlock extends Base {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowed();
    if ($entity = $this->getEntity()) {
      return $entity->access('view', $account, TRUE);
    }
    return AccessResult::forbidden();
  }

}
