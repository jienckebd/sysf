<?php

namespace Drupal\bd\Menu;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Menu\MenuLinkDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Nav.
 */
class Nav implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * Map menu plugin types to their classes.
   *
   * @var array
   */
  const MAP_MENU_PLUGIN_CLASS = [
    'link' => MenuLinkDefault::class,
    'action' => LocalActionDefault::class,
    'task' => LocalTaskDefault::class,
  ];

  /**
   * Menu links to remove.
   *
   * @var array
   */
  const MENU_LINK_ID_REMOVE = [
    'admin_toolbar_tools.system.theme_settings',
    'update.theme_install_',
    'update.theme_update_',
    'contact.site_page',
  ];

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;

  /**
   * Nav constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.helper'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * @param $entity_type_id
   * @param $rel_id
   *
   * @return string
   */
  public function entityRelToRouteName($entity_type_id, $rel_id) {
    $route_name = "entity.{$entity_type_id}.{$rel_id}";

    // Replace characters.
    $route_name = str_replace(['-'], ['_'], $route_name);

    return $route_name;
  }

  /**
   * Process menu link, action, and task plugins.
   *
   * This is used as opposed to menu link derivatives because this can both
   * build new and alter existing plugins.
   *
   * @param string $plugin_type
   * @param array $plugins
   */
  public function processMenuPlugin($plugin_type, array &$plugins) {
    foreach ($this->entityHelper->getDefinitions() as $entity_type_id => $entity_type) {

      if (!$menu_config = $entity_type->get('menu')) {
        continue;
      }

      if (empty($menu_config[$plugin_type])) {
        continue;
      }

      if ($entity_type_id == 'relation') {
        $d = 1;
      }

      foreach ($menu_config[$plugin_type] as $id => $link_config) {
        $plugin_definition = $link_config;

        $plugin_definition['provider'] = 'bd';
        $plugin_definition['class'] = static::MAP_MENU_PLUGIN_CLASS[$plugin_type];

        $plugin_definition['rel'] = !empty($link_config['rel']) ? $link_config['rel'] : "collection";
        $plugin_definition['route_name'] = $this->entityRelToRouteName($entity_type_id, $plugin_definition['rel']);
        $plugin_definition['title'] = $this->t($link_config['label']);

        if (!isset($plugin_definition['weight'])) {
          $plugin_definition['weight'] = NULL;
        }

        if (!isset($plugin_definition['options'])) {
          $plugin_definition['options'] = [];
        }

        $plugin_definition['id'] = $plugin_definition['route_name'];
        $plugins[$plugin_definition['id']] = $plugin_definition;
      }

    }

    if ($plugin_type == 'link') {
      $this->processMenuLinkRemove($plugins);
    }
  }

  /**
   * @param array $plugins
   */
  protected function processMenuLinkRemove(array &$plugins) {
    foreach (static::MENU_LINK_ID_REMOVE as $menu_link_id) {
      if (!empty($plugins[$menu_link_id])) {
        unset($plugins[$menu_link_id]);
      }
    }
  }

}
