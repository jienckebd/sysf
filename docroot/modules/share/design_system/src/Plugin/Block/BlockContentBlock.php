<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\block_content\Plugin\Block\BlockContentBlock as Base;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a generic custom block type.
 *
 * @Block(
 *  id = "block_content",
 *  admin_label = @Translation("Custom block"),
 *  category = @Translation("Custom"),
 *  deriver = "Drupal\block_content\Plugin\Derivative\BlockContent"
 * )
 */
class BlockContentBlock extends Base {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowed();
    if ($this->getEntity()) {
      return $this->getEntity()->access('view', $account, TRUE);
    }
    return AccessResult::forbidden();
  }

}
