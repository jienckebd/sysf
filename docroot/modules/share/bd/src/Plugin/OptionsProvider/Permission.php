<?php

namespace Drupal\bd\Plugin\OptionsProvider;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an option provider for languages.
 *
 * @OptionsProvider(
 *   plugin_type = "options_provider",
 *   id = "permission",
 *   label = @Translation("Permissions"),
 *   description = @Translation("Provides permissions."),
 * )
 */
class Permission extends Base {

  /**
   * {@inheritDoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {

    /** @var \Drupal\user\PermissionHandlerInterface $permission_handler */
    $permission_handler = \Drupal::service('user.permissions');

    $option = [];

    foreach ($permission_handler->getPermissions() as $permission_id => $permission) {
      $option[$permission_id] = $permission['title'];
    }

    return $option;

  }

}
