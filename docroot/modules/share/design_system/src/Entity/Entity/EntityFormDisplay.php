<?php

namespace Drupal\design_system\Entity\Entity;

use Drupal\Core\Render\Element;
use Drupal\Core\Entity\Entity\EntityFormDisplay as Base;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extends layout_builder and field_ui.
 */
class EntityFormDisplay extends Base {

  use EntityDisplayTrait;

  /**
   * The entity view display.
   *
   * @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay
   */
  protected $entityViewDisplay;

  /**
   * The form display components set in layout builder components.
   *
   * @var array
   */
  protected $entityViewDisplayComponents;

  /**
   * {@inheritDoc}
   */
  public function getComponents() {
    if (!$this->isLayoutBuilderEnabled()) {
      return parent::getComponents();
    }
    return $this->entityViewDisplayComponents;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent($name) {
    if (!$this->isLayoutBuilderEnabled()) {
      return parent::getComponent($name);
    }

    $components = $this->getComponents();
    return isset($components[$name]) ? $components[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setComponent($name, array $options = []) {
    if (!$this->isLayoutBuilderEnabled()) {
      return parent::setComponent($name, $options);
    }

    if (!isset($options['settings'])) {
      $options['settings'] = [];
    }
    if (!isset($options['third_party_settings'])) {
      $options['third_party_settings'] = [];
    }

    $this->entityViewDisplayComponents[$name] = $options;

    return $this;
  }

  /**
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isLayoutBuilderEnabled() {

    $target_entity_type = $this->entityHelper()->getDefinition($this->getTargetEntityTypeId());

    $form_config = $target_entity_type->get('display') ?: [];

    if (empty($form_config['form']['entity_view_display']['enabled'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @return \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityViewDisplay() {

    if (isset($this->entityViewDisplay)) {
      return $this->entityViewDisplay;
    }

    $target_entity_type_id = $this->getTargetEntityTypeId();
    $target_bundle_id = $this->getTargetBundle();
    $form_mode_id = $this->getMode();

    $form_view_mode_id = "{$target_entity_type_id}.{$target_bundle_id}.form__{$form_mode_id}";
    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay|null $entity_view_display */
    $this->entityViewDisplay = $this->entityHelper()->getStorage('entity_view_display')->load($form_view_mode_id);

    if (empty($this->entityViewDisplay)) {
      $form_view_mode_id = "{$target_entity_type_id}.{$target_bundle_id}.form__default";
      /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay|null $entity_view_display */
      $this->entityViewDisplay = $this->entityHelper()->getStorage('entity_view_display')->load($form_view_mode_id);
    }

    return $this->entityViewDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {

    foreach (\Drupal::request()->query as $key => $value) {
      if ($entity->hasField($key)) {
        // @todo check access to edit field.
        $value = @unserialize($value);
        $entity->set($key, $value);
      }
    }

    if (!$this->isLayoutBuilderEnabled()) {
      return parent::buildForm($entity, $form, $form_state);
    }

    if (!$entity_view_display = $this->getEntityViewDisplay()) {
      return [];
    }

    if (empty($_ENV['SYS_TMP_ENTITY_DISPLAY_CONTEXT_FORM'])) {
      $_ENV['SYS_TMP_ENTITY_DISPLAY_CONTEXT_FORM'] = $entity;
      $_ENV['SYS_TMP_ENTITY_DISPLAY_CONTEXT_FORM_MODE'] = $this->getMode();
    }

    // Set #parents to 'top-level' by default.
    $form += ['#parents' => []];

    $entity_type_id = $entity->getEntityTypeId();
    $form_view_mode_id = $this->entityViewDisplay->getMode();

    $entity_view_builder = $this->entityHelper()->getViewBuilder($entity_type_id);
    $form['#entity_view_display'] = $this->entityHelper()->getViewBuilder($entity_type_id)
      ->view($entity, $form_view_mode_id);

    $form['#entity_view_display'] = $entity_view_builder->build($form['#entity_view_display']);
    $this->recurseAttachMockFieldWidgetElements($form['#entity_view_display'], $form);

    // Associate the cache tags for the form display.
    $this->renderer->addCacheableDependency($form, $this);

    $form['#pre_render'][] = [$this, 'preRenderEntityFormDisplay'];

  }

  /**
   * @param array $element
   * @param array $complete_form
   */
  protected function recurseAttachMockFieldWidgetElements(array &$element, array &$complete_form) {

    foreach (Element::children($element) as $child_key) {

      $child = &$element[$child_key];
      if (!is_array($child)) {
        continue;
      }

      if (!empty($child['#mock_field_widget_config']) && !empty($child['#mock_field_widget_name'])) {

        $mock_field_widget_block_configuration = $child['#mock_field_widget_config'];
        $mock_field_widget_configuration = isset($mock_field_widget_block_configuration['widget']) ? $mock_field_widget_block_configuration['widget'] : [];
        $mock_field_widget_field_name = $child['#mock_field_widget_name'];

        if ($mock_field_widget_field_name == 'field_color_scheme') {
          unset($element[$child_key]);
          continue;
        }

        $complete_form[$mock_field_widget_field_name] = &$child;
        $this->setComponent($mock_field_widget_field_name, $mock_field_widget_configuration);

      }

      $this->recurseAttachMockFieldWidgetElements($child, $complete_form);

    }

  }

  /**
   * @param $element
   *
   * @return mixed
   */
  public function preRenderEntityFormDisplay($element) {
    if (empty($element['#entity_view_display'])) {
      return $element;
    }

    foreach (Element::children($element) as $child_key) {

      $child = &$element[$child_key];
      if (!is_array($child)) {
        continue;
      }

      // Base entity form class adds actions key so need to also explicitly
      // check keys.
      if (isset($child['#mock_field_widget_name']) || in_array($child_key, ['actionstmp'])) {
        unset($element[$child_key]);
      }

    }

    $element['entity_view_display'] = $element['#entity_view_display'];
    unset($element['#entity_view_display']);
    return $element;
  }

}
