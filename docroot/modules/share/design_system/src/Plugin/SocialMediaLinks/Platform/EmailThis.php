<?php

namespace Drupal\design_system\Plugin\SocialMediaLinks\Platform;

use Drupal\social_media_links\PlatformBase;
use Drupal\Core\Url;

/**
 * Provides 'email this' platform.
 *
 * @Platform(
 *   id = "email_this",
 *   name = @Translation("Email this"),
 *   iconName = "email",
 * )
 */
class EmailThis extends PlatformBase {

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri('mailto:' . $this->getValue());
  }

}
