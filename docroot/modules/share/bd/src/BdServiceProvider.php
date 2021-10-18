<?php

namespace Drupal\bd;

use Drupal\bd\DependencyInjection\ServiceProviderBase;
use Drupal\bd\File\FileSystem;
use Drupal\bd\Form\FormBuilder;
use Drupal\bd\Config\TypedConfigManager;
use Drupal\bd\Logger\LoggerChannelFactory;
use Drupal\bd\Config\ConfigFactory;

/**
 * Replace core and contrib services and provide new ones.
 */
class BdServiceProvider extends ServiceProviderBase {

  /**
   * The services to override.
   *
   * @var array
   */
  const SERVICE_OVERRIDE = [
    'alter' => [
      'config.factory' => [
        'class' => ConfigFactory::class,
      ],
      'form_builder' => [
        'class' => FormBuilder::class,
      ],
      'file_system' => [
        'class' => FileSystem::class,
      ],
    ],
  ];

}
