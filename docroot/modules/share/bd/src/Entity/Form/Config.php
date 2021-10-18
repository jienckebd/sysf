<?php

namespace Drupal\bd\Entity\Form;

use Drupal\bd\Php\Str;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Generic config entity form.
 */
class Config extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($entity_type_id != 'base_field_override') {
      return parent::getEntityFromRouteMatch($route_match, $entity_type_id);
    }

    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      $entity = $route_match->getParameter($entity_type_id);
    }
    else {
      $values = [];
      // If the entity has bundles, fetch it from the route match.
      $entity_type = $this->entityHelper->getDefinition($entity_type_id);
      if ($bundle_key = $entity_type->getKey('bundle')) {
        if (($bundle_entity_type_id = $entity_type->getBundleEntityType()) && $route_match->getRawParameter($bundle_entity_type_id)) {
          $values[$bundle_key] = $route_match->getParameter($bundle_entity_type_id)->id();
        }
        elseif ($route_match->getRawParameter($bundle_key)) {
          $values[$bundle_key] = $route_match->getParameter($bundle_key);
        }
      }

      // @todo temporary workaround. Base field override entity requires these
      // properties to be set in order to ::create().
      if ($entity_type_id == 'base_field_override') {
        $values['field_name'] = 'title';
        $values['entity_type'] = 'node';
        $values['bundle'] = 'page';
      }

      $entity = $this->entityHelper->getStorage($entity_type_id)->create($values);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->entity->isNew()) {
      $settings = [];
    }
    else {
      $settings = $this->entity->toArray();
    }

    $config_prefix = $this->getEntity()->getEntityType()->getConfigPrefix();
    $config_schema_id = "{$config_prefix}.*";

    if ($this->entity->getEntityTypeId() == 'base_field_override') {
      $config_schema_id = 'core.base_field_override.*.*.*';
    }

    $form['subform'] = [
      '#type' => 'config_schema_subform',
      '#config_schema_id' => $config_schema_id,
      '#config_data' => $settings,
      '#entity' => $this->entity,
      '#is_new' => $this->entity->isNew(),
    ];

    if (in_array($this->entity->getEntityTypeId(), ['base_field_override', 'bundle_field_definition'])) {
      $form['subform']['third_party_settings']['bd'] = [
        '#type' => 'config_schema_subform',
        '#config_schema_id' => 'field_definition.third_party_settings',
        '#config_data' => $this->entity->getThirdPartySettings('bd'),
        '#entity' => $this->entity,
        '#is_new' => $this->entity->isNew(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Button-level validation handlers are highly discouraged for entity forms,
   * as they will prevent entity validation from running. If the entity is going
   * to be saved during the form submission, this method should be manually
   * invoked from the button-level validation handler, otherwise an exception
   * will be thrown.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->buildEntity($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {

    $values = $form_state->getValue('subform');

    // @todo config_schema.
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        if (isset($value['widget']['value'])) {
          $values[$key] = $value['widget']['value'];
        }
      }
    }

    $entity_type = $entity->getEntityType();

    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $values = array_diff_key($values, $this->entity->getPluginCollections());
    }

    // Clear existing values.
    $properties = $entity->getEntityType()->getPropertiesToExport();

    foreach ($values as $key => $value) {
      if (is_array($value) && count($value) == 1 && isset($value['value'])) {
        $value = $value['value'];
      }
      $entity->set($key, $value);
    }

    $route_params = \Drupal::routeMatch()->getParameters();
    foreach ($route_params as $param_id => $param_value) {
      if ($param_value instanceof EntityInterface) {
        $entity_from_route = $param_value;
        break;
      }
    }

    if ($entity->isNew()) {

      $variables = [];

      if (!empty($entity_from_route)) {
        $target_entity_type_id = $entity_from_route->getEntityType()
          ->getBundleOf();
        $target_bundle_id = $entity_from_route->id();

        $variables['entity_type'] = $target_entity_type_id;
        $variables['bundle'] = $target_bundle_id;

        $entity->set('entity_type', $target_entity_type_id);
        $entity->set('bundle', $target_bundle_id);

        if (!empty($properties['field_type'])) {
          if (!empty($values['field_name'])) {
            $field_type = \Drupal::service('entity_field.manager')
              ->getBaseFieldDefinitions($target_entity_type_id)[$values['field_name']]->getType();
            $entity->set('field_type', $field_type);
          }
        }
      }

      if ($id_template = $entity->getEntityType()->get('id_template')) {
        foreach ($entity as $key => $value) {
          $variables[$key] = $value;
        }

        $variables['label_machine_name'] = Str::sanitizeMachineName($entity->label());

        foreach ($variables as $key => $value) {
          $variables["{{ {$key} }}"] = $value;
          unset($variables[$key]);
        }

        $new_entity_id = str_replace(array_keys($variables), array_values($variables), $id_template);
      }
      else {
        $new_entity_id = Str::sanitizeMachineName($entity->label());
      }

      $entity_key_id = $entity_type->getKey('id');
      $entity->set($entity_key_id, $new_entity_id);

    }

  }

}
