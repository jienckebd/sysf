<?php

namespace Drupal\design_system\Form\EntityDisplay;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Form\LayoutBuilderEntityViewDisplayForm as Base;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\design_system\DesignSystem;

/**
 * Extends field_ui and layout_builder.
 *
 * @internal
 */
class EntityViewDisplayEditForm extends Base {

  use EntityDisplayFormTrait;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * FieldLayoutEntityViewDisplayEditForm constructor.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The formatter plugin manager.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The field layout plugin manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display_repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system/.
   */
  public function __construct(
    FieldTypePluginManagerInterface $field_type_manager,
    PluginManagerBase $plugin_manager,
    LayoutPluginManagerInterface $layout_plugin_manager,
    EntityDisplayRepositoryInterface $entity_display_repository = NULL,
    EntityFieldManagerInterface $entity_field_manager = NULL,
    DesignSystem $design_system = NULL,
    TypedConfigManagerInterface $typed_config_manager = NULL
  ) {
    parent::__construct(
      $field_type_manager,
      $plugin_manager,
      $entity_display_repository,
      $entity_field_manager
    );
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->designSystem = $design_system ?: \Drupal::service('design.system');
    $this->typedConfigManager = $typed_config_manager ?: \Drupal::service('config.typed');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.core.layout'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('design.system'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function thirdPartySettingsForm(PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $settings_form = parent::thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);
    return $settings_form;
  }

}
