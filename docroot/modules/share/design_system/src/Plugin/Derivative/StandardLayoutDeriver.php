<?php

namespace Drupal\design_system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;

/**
 * Provides a deriver around layouts.
 */
class StandardLayoutDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Constructs new FieldBlockDeriver.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   */
  public function __construct(
    LayoutPluginManagerInterface $layout_plugin_manager
  ) {
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    foreach ($this->layoutPluginManager->getDefinitions() as $plugin_id => $layout_plugin_definition) {
      if ($layout_plugin_definition->getProvider() != 'design_system') {
        continue;
      }

      $derivative = $base_plugin_definition;
      $derivative['admin_label'] = $this->t('Layout block: @layout_label', [
        '@layout_label' => $layout_plugin_definition->getLabel(),
      ]);
      $derivative['layout_plugin_id'] = $plugin_id;
      $this->derivatives[$plugin_id] = $derivative;
    }

    return $this->derivatives;
  }

}
