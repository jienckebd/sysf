<?php

namespace Drupal\design_system\Form\EntityDisplay;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\design_system\DesignSystem;
use Drupal\field_ui\Form\EntityFormDisplayEditForm as Base;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends field_ui and layout_builder.
 */
class EntityFormDisplayEditForm extends Base {

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
   * Constructs a new EntityDisplayFormBase.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The widget or formatter plugin manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null $entity_display_repository
   *   (optional) The entity display_repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   (optional) The entity field manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager, PluginManagerBase $plugin_manager, EntityDisplayRepositoryInterface $entity_display_repository = NULL, EntityFieldManagerInterface $entity_field_manager = NULL, DesignSystem $design_system = NULL, TypedConfigManagerInterface $typed_config_manager = NULL) {
    parent::__construct($field_type_manager, $plugin_manager, $entity_display_repository, $entity_field_manager);
    $this->designSystem = $design_system ?: \Drupal::service('design.system');
    $this->typedConfigManager = $typed_config_manager ?: \Drupal::service('config.typed');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.widget'),
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

    if (!empty($settings_form['maxlength'])) {
      $settings_form['maxlength']['maxlength_js_label']['#default_value'] = $this->t('@remaining / @limit remaining');
    }

    return $settings_form;
  }

}
