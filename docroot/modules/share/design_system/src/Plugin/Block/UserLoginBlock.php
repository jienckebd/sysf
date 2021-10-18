<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\user\Plugin\Block\UserLoginBlock as Base;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'User login' block.
 *
 * @Block(
 *   id = "user_login_block",
 *   admin_label = @Translation("User login"),
 *   category = @Translation("Forms")
 * )
 */
class UserLoginBlock extends Base {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed()
        ->addCacheContexts(['route.name', 'user.roles:anonymous']);
    }
    return AccessResult::forbidden();
  }

}
