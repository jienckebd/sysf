<?php

namespace Drupal\design_system\Element;

use Drupal\toolbar\Element\Toolbar as Base;

/**
 * Extends core toolbar functionality.
 */
class Toolbar extends Base {

  /**
   * @param array $items
   */
  public static function alterToolbar(array &$items = []) {

    /** @var \Drupal\design_system\DesignSystem $design_system */
    $design_system = \Drupal::service('design.system');

    if (!empty($items['block_place'])) {
      $access = \Drupal::currentUser()->hasPermission('administer blocks');
      $items['block_place']['tab']['#access'] = $access;
    }

    $items_hide = [
      'acquia_connector' => [],
      'home' => [],
    ];

    $items_left = [
      'home' => [],
      'contextual' => [
        'tab' => [
          'class' => [
            'bg-info',
            'w--text-black',
          ],
        ],
      ],
      'block_place' => [],
      'responsive_preview' => [],
      'shortcuts' => [],
      'devel' => [],
      'administration' => [],
    ];

    $items_right = [
      'workspace' => [
        'tab' => [
          'class' => [
            'bg-success',
            'w--text-black',
          ],
        ],
      ],
      'administration_search' => [],
      'user' => [],
    ];

    // Reverse order because of float-right class.
    $items_right = array_reverse($items_right);

    foreach ($items_hide as $item_id => $item_config) {
      if (!empty($items[$item_id])) {
        unset($items[$item_id]);
      }
    }

    $weight = 0;
    foreach ($items_left as $item_id => $item_config) {
      if (empty($items[$item_id])) {
        continue;
      }
      $child = &$items[$item_id];
      static::processToolbarTab($child, $item_config, 'float-left');
      $child['#weight'] = $weight;
      $weight += 10;
    }

    $weight = 0;
    foreach ($items_right as $item_id => $item_config) {
      if (empty($items[$item_id])) {
        continue;
      }
      $child = &$items[$item_id];
      static::processToolbarTab($child, $item_config, 'float-right');
      $child['#weight'] = $weight;
      $weight += 10;
    }

    // Run our overrides after admin_toolbar.
    if (!empty($items['administration']['tray']['toolbar_administration'])) {
      $items['administration']['tray']['toolbar_administration']['#pre_render'][] = [static::class, 'preRenderAdminToolbar'];
    }

    if ($map_tab_icon = $design_system->getOption('toolbar.tab_icon')) {
      foreach ($map_tab_icon as $toolbar_id => $icon) {
        if (!empty($items[$toolbar_id]['tab'])) {
          $items[$toolbar_id]['tab']['#icon'] = $icon;
          $items[$toolbar_id]['tab']['#icon_size'] = 'fa-lg';
        }
      }
    }

  }

  /**
   * Process toolbar tab.
   *
   * @config toolbar.tab
   *
   * @param array $tab
   * @param array $config
   * @param $float_class
   */
  protected static function processToolbarTab(array &$tab, array $config, $float_class) {
    $tab['#wrapper_attributes']['class'][] = $float_class;

    if (!empty($config['tab']['class'])) {
      foreach ($config['tab']['class'] as $key => $class) {
        $tab['#wrapper_attributes']['class'][] = $class;
      }
    }
  }

  /**
   * Pre render callback for admin toolbar admin menu child element.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The processed render element.
   */
  public static function preRenderAdminToolbar(array $element) {
    if (empty($element['administration_menu']['#items'])) {
      return $element;
    }

    // @todo get icon from menu_link_content entity field.
    return $element;

    // Apply icons without loading and rendering so many entities every request.
    if (!$config_icon = \Drupal::service('design.system')->getOption('icon.route_name')) {
      return $element;
    }

    // Flip the array for faster checks.
    static::recurseSetIcon($element['administration_menu']['#items'], $config_icon);

    return $element;
  }

  /**
   * Recursively attach icons to the toolbar.
   *
   * @param array $element
   *   The render element.
   * @param array $config_icon
   *   The icon config.
   */
  public static function recurseSetIcon(array &$element, array &$config_icon) {

    foreach ($element as $key => &$child) {
      if (is_array($child)) {
        if (isset($config_icon[$key])) {
          $child['title'] = [
            'icon' => [
              '#type' => 'icon',
              '#icon' => $config_icon[$key],
            ],
            'title' => [
              '#markup' => $child['title'],
            ],
          ];
        }

        static::recurseSetIcon($child, $config_icon);
      }
    }

  }

}
