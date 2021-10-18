<?php

namespace Drupal\bd\Sys;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;

/**
 * Class Sys.
 *
 * @package Drupal\bd\Service
 */
class Deploy {

  /**
   * @var \Drupal\bd\Entity\EntityHelper
   */
  public $entityHelper;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * The current user injected into the service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $currentUser;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  public $sessionManager;

  /**
   * Sys constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(EntityHelper $entity_helper, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, AccountInterface $current_user, SessionManagerInterface $session_manager) {
    $this->entityHelper = $entity_helper;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
  }

  /**
   * Post deployment logic.
   */
  public function postDeploy() {

    /** @var \Drupal\bd\Service\Internal $sys_manager_internal */
    $sys_manager_internal = \Drupal::service('sys.internal');

    $block_vote = $this->entityHelper
      ->getStorage('block_content')
      ->load(11);
    $block_vote_view = [
      [
        'target_id' => 'node_tags_vote',
        'display_id' => 'block_1',
        'argument' => NULL,
        'title' => 0,
        'data' => NULL,
      ],
    ];
    $block_vote->set('field_view', $block_vote_view);

    $block_vote_view = [
      'uri' => 'internal:/ajax/modal/entityform/taxonomy_term/tags',
      'title' => 'Add a new tag',
    ];
    $block_vote->set('field_x_action', $block_vote_view);

    $block_vote->save();

    $block_vote = $this->entityHelper
      ->getStorage('block_content')
      ->load(12);
    $block_vote_view = [
      [
        'target_id' => 'node_related_blocks',
        'display_id' => 'block_1',
        'argument' => NULL,
        'title' => 0,
        'data' => NULL,
      ],
      [
        'target_id' => 'node_related_blocks',
        'display_id' => 'block_2',
        'argument' => NULL,
        'title' => 0,
        'data' => NULL,
      ],
    ];
    $block_vote->set('field_view', $block_vote_view);
    $block_vote->save();

    $sys_manager_internal->entityBulkUpdate->byBundle('node', ['podcast'], [
      'field_image' => [
        'target_id' => 9833,
      ],
    ]);

    // Set all taxonomy terms to published.
    $sys_manager_internal->entityBulkUpdate->byEntityType('taxonomy_term', [
      'status' => 1,
    ]);

    $sys_manager_internal->entityBulkUpdate->byBundle('taxonomy_term', ['tags'], [
      'field_autoref' => [
        'target_id' => 5,
      ],
    ]);

    $home_node = $this->entityHelper->getStorage('node')->load(14033);
    $panelizer_data = 'a:13:{s:6:"blocks";a:4:{s:36:"710b7bbc-843e-42e9-9c26-d9fc60427f71";a:11:{s:2:"id";s:50:"block_content:b475195a-96a0-48d3-9bab-e832f854881a";s:5:"label";s:15:"Animated Banner";s:8:"provider";s:13:"block_content";s:13:"label_display";i:0;s:6:"status";b:1;s:4:"info";s:0:"";s:9:"view_mode";s:4:"full";s:15:"context_mapping";a:0:{}s:6:"region";s:6:"header";s:4:"uuid";s:36:"710b7bbc-843e-42e9-9c26-d9fc60427f71";s:6:"weight";i:1;}s:36:"0d4650eb-22f1-4a50-9abe-a79fe3229f53";a:10:{s:2:"id";s:36:"views_block:most_recent_node-block_1";s:5:"label";s:0:"";s:8:"provider";s:5:"views";s:13:"label_display";i:0;s:11:"views_label";s:0:"";s:14:"items_per_page";s:4:"none";s:15:"context_mapping";a:0:{}s:6:"region";s:6:"header";s:4:"uuid";s:36:"0d4650eb-22f1-4a50-9abe-a79fe3229f53";s:6:"weight";i:2;}s:36:"efa1421d-a283-4963-b7a3-06eb30855528";a:10:{s:2:"id";s:36:"views_block:node_home_blocks-block_2";s:5:"label";s:28:"<small>News</small> Articles";s:8:"provider";s:5:"views";s:13:"label_display";s:7:"visible";s:11:"views_label";s:28:"<small>News</small> Articles";s:14:"items_per_page";s:4:"none";s:15:"context_mapping";a:0:{}s:6:"region";s:7:"column2";s:4:"uuid";s:36:"efa1421d-a283-4963-b7a3-06eb30855528";s:6:"weight";i:1;}s:36:"a0d23b7d-0f39-4407-9fe4-a3a45d178929";a:10:{s:2:"id";s:36:"views_block:node_home_blocks-block_1";s:5:"label";s:32:"<small>arXiv</small> Whitepapers";s:8:"provider";s:5:"views";s:13:"label_display";s:7:"visible";s:11:"views_label";s:32:"<small>arXiv</small> Whitepapers";s:14:"items_per_page";s:4:"none";s:15:"context_mapping";a:0:{}s:6:"region";s:7:"column1";s:4:"uuid";s:36:"a0d23b7d-0f39-4407-9fe4-a3a45d178929";s:6:"weight";i:1;}}s:2:"id";s:14:"panels_variant";s:4:"uuid";s:36:"1a312aa0-75a2-4467-8421-33670a7bd039";s:5:"label";s:13:"Single Column";s:6:"weight";i:0;s:6:"layout";s:11:"radix_sutro";s:15:"layout_settings";a:0:{}s:10:"page_title";s:12:"[node:title]";s:12:"storage_type";s:15:"panelizer_field";s:10:"storage_id";s:21:"node:14033:full:14934";s:7:"pattern";s:9:"panelizer";s:7:"builder";s:3:"ipe";s:14:"static_context";a:0:{}}';
    $home_node->set('panelizer', [
      'view_mode' => 'full',
      'default' => '__bundle_default__',
      'panels_display' => unserialize($panelizer_data),
    ]);
    $home_node->save();

  }

}
