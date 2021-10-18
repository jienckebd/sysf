<?php

namespace Drupal\design_system\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Url;

/**
 * Provides a common icon select element.
 *
 * @FormElement("icon_select")
 */
class IconSelect extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    array_unshift($info['#process'], [$class, 'processIconSelect']);
    return $info;
  }

  /**
   * Adds icon select.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #target_type: The ID of the target entity type.
   *   - #selection_handler: The plugin ID of the entity reference selection
   *     handler.
   *   - #selection_settings: An array of settings that will be passed to the
   *     selection handler.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the #target_type or #autocreate['bundle'] are
   *   missing.
   */
  public static function processIconSelect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element += [
      '#type' => 'textfield',
      '#title' => t('Icon Name'),
      '#required' => $element['#required'],
      '#size' => 50,
      '#default_value' => !empty($element['#default_value']) ? $element['#default_value'] : '',
      '#description' => t('Pick an icon.'),
      '#autocomplete_route_name' => 'fontawesome.autocomplete',
      '#element_validate' => [
        [static::class, 'validateIconName'],
      ],
    ];
    $element['#autocomplete_route_name'] = 'fontawesome.autocomplete';
    return $element;
  }

  /**
   * Validate the Font Awesome icon name.
   */
  public static function validateIconName($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }

    // Load the icon data so we can check for a valid icon.
    $iconData = fontawesome_extract_icon_metadata($value);

    if (!isset($iconData['name'])) {
      $form_state->setError($element, t("Invalid icon name %value. Please see @iconLink for correct icon names.", [
        '%value' => $value,
        '@iconLink' => Link::fromTextAndUrl(t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons'))->toString(),
      ]));
    }
  }

}
