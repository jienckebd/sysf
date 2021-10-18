<?php

namespace Drupal\bd\Entity\Form\Field;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait FieldEditFormTrait.
 *
 * @package Drupal\bd\Entity\Form\Field
 */
trait FieldEditFormTrait {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($this->entity->getEntityTypeId() != 'field_config') {
      return $form;
    }

    $config_data = $this->entity->getThirdPartySettings('bd');

    $form['third_party_settings']['bd'] = [
      '#type' => 'config_schema_subform',
      '#config_schema_id' => 'field_definition.third_party_settings',
      '#config_data' => $this->entity->getThirdPartySettings('bd'),
      '#entity' => $this->entity,
      '#is_new' => empty($config_data),
    ];

    // Entity default value provided by bd config schema.
    if (!empty($form['default_value'])) {
      unset($form['default_value']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $third_party_settings = $this->entity->get('third_party_settings');

    if (!empty($third_party_settings['bd']['behavior']['default_value'])) {
      $this->entity->set('default_value_callback', '\Drupal\bd\Entity\EntityFieldDefaultValue::derivedDefaultValue');
    }
    else {
      if ($this->entity->get('default_value_callback') == '\Drupal\bd\Entity\EntityFieldDefaultValue::derivedDefaultValue') {
        $this->entity->set('default_value_callback', NULL);
      }
    }

  }

}
