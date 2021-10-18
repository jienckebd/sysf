<?php

namespace Drupal\design_system\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Extends core select.
 */
class Normalizer implements TrustedCallbackInterface {

  /**
   * Elements to add group processing.
   */
  const ELEMENT_ADD_GROUP = [
    'actions',
    'link',
    'select',
    'item',
    'email',
    'password',
  ];

  /**
   * Elements to add button functionality.
   *
   * @var array
   */
  const ELEMENT_BUTTON = [
    'button',
    'submit',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderNormalizeFirst',
      'preRenderNormalizeLast',
      'preRenderConfigAjax',
      'preRenderPrefixSuffix',
      'preRenderConfigButton',
      'preRenderGroup',
      'preRenderToolbar',
      'preRenderView',
      'preRenderDropbutton',
      'preRenderSelectLast',
    ];
  }

  /**
   * @param array $info
   */
  public static function alterElementInfo(array &$info) {

    // Make sure our pre render goes before link element pre render because that
    // renders the markup we need to adjust structurally.
    array_unshift($info['link']['#pre_render'], [Normalizer::class, 'preRenderConfigAjax']);

    // Support render arrays on prefix and suffix properties of elements.
    array_unshift($info['html_tag']['#pre_render'], [Normalizer::class, 'preRenderPrefixSuffix']);

    foreach (static::ELEMENT_BUTTON as $element_id) {
      array_unshift($info[$element_id]['#pre_render'], [Normalizer::class, 'preRenderConfigButton']);
    }

    foreach (static::ELEMENT_ADD_GROUP as $element_id) {
      if (empty($info[$element_id])) {
        continue;
      }

      $element_info = &$info[$element_id];

      $element_info['#process'][] = [RenderElement::class, 'processGroup'];
      $element_info['#pre_render'][] = [RenderElement::class, 'preRenderGroup'];
    }

    if (!empty($info['toolbar'])) {
      $info['toolbar']['#pre_render'][] = [static::class, 'preRenderToolbar'];
      $info['toolbar']['#cache']['keys'][] = 'toolbar';
    }

    if (!empty($info['view'])) {
      $info['view']['#pre_render'][] = [static::class, 'preRenderView'];
    }

    if (!empty($info['text_format'])) {
      $info['text_format']['#process'][] = [static::class, 'processTextFormat'];
    }

    if (!empty($info['details'])) {
      $info['details']['#process'][] = [static::class, 'processDetails'];
    }

    if (!empty($info['operations'])) {
      // $info['operations']['#pre_render'][] = [static::class, 'preRenderDropbutton'];
    }

    if (!empty($info['dropzonejs'])) {
      $info['dropzonejs']['#process'][] = [static::class, 'processDropzoneJs'];
    }

    $info['select']['#select2'] = TRUE;
    array_unshift($info['select']['#process'], [Normalizer::class, 'processSelectFirst']);
    $info['select']['#pre_render'][] = [Normalizer::class, 'preRenderSelectLast'];

    // Attach process and pre_render callback to all elements.
    foreach ($info as $key => &$child) {
      if (!empty($child['#input'])) {
        if (empty($child['#process'])) {
          $child['#process'] = [];
        }
        array_unshift($child['#process'], [Normalizer::class, 'processNormalizeFirst']);
        $child['#process'][] = [static::class, 'processNormalizeLast'];
      }
      if (empty($child['#pre_render'])) {
        $child['#pre_render'] = [];
      }
      array_unshift($child['#pre_render'], [Normalizer::class, 'preRenderNormalizeFirst']);
      $child['#pre_render'][] = [static::class, 'preRenderNormalizeLast'];
    }

  }

  /**
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function processSelectFirst($element, FormStateInterface $form_state, &$complete_form) {

    if (!empty($element['#options_provider'])) {

      $plugin_id_options_provider = $element['#options_provider']['plugin_id'];
      $plugin_config_options_provider = $element['#options_provider']['plugin_config'] ?? [];

      /** @var \Drupal\bd\PluginManager\EntityPluginManager $plugin_manager_options_provider */
      $plugin_manager_options_provider = \Drupal::service('plugin.manager.options_provider');
      $plugin_instance_options_provider = $plugin_manager_options_provider->createInstance($plugin_id_options_provider, $plugin_config_options_provider);

      $element['#options'] = $plugin_instance_options_provider->getPossibleOptions();
    }

    if (!empty($element['#normalize'])) {
      $element['#empty_option'] = t('- Select -');
    }

    return $element;
  }

  /**
   * @param $element
   *
   * @return mixed
   */
  public static function preRenderSelectLast($element) {

    $options = $element['#attached']['drupalSettings']['select2']['options'] ?? [];
    //
    //    $options['allowClear'] = TRUE;
    //    $options['placeholder'] = t('');
    $element['#attached']['drupalSettings']['select2']['options'] = $options;

    return $element;
  }

  /**
   * Process dropzonejs element.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return mixed
   */
  public static function processDropzoneJs($element, FormStateInterface $form_state, &$complete_form) {

    $element['#attributes']['class'][] = 'bg-darker';
    $element['#attributes']['class'][] = 'text-white';

    return $element;
  }

  /**
   * Process callback for all input elements.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return mixed
   */
  public static function processNormalizeFirst($element, FormStateInterface $form_state, &$complete_form) {
    return $element;
  }

  /**
   * Pre render callback for all render elements.
   *
   * @param array $element
   *
   * @return array
   */
  public static function preRenderNormalizeFirst(array $element) {

    if (!empty($element['#hotkey'])) {
      $element['#attributes']['class'][] = 'hotkey--target';
      $element['#attributes']['data-hotkey'] = $element['#hotkey'];
    }

    if (!empty($element['#array_parents'])) {
      $element['#attributes']['data-form-key'] = implode('.', $element['#array_parents']);
    }

    if (!empty($element['#attributes']['style'])) {
      static::convertStyleArrayToString($element);
    }

    return $element;
  }

  /**
   * Process callback for all input elements.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return mixed
   */
  public static function processNormalizeLast(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#type'] == 'text_format') {
      $d = 1;
    }
    return $element;
  }

  /**
   * Pre render callback for all render elements.
   *
   * @param array $element
   *
   * @return array
   */
  public static function preRenderNormalizeLast(array $element) {
    return $element;
  }

  /**
   * @param array $element
   *
   * @return array
   */
  public static function preRenderDropbutton(array $element) {

    if (empty($element['#links'])) {
      return $element;
    }

    unset($element['#theme']);
    $element['#type'] = 'container';
    $element['#attributes']['class'][] = 'btn-group';
    $element['#attached']['library'][] = 'alpha/bootstrap.dropdown';
    $element['#theme_wrappers'] = ['container'];

    $primary_button = array_shift($element['#links']);

    if (!empty($primary_button['url'])) {

      /** @var \Drupal\Core\Url $url */
      $url = $primary_button['url'];
      if (!empty($primary_button['query'])) {
        $url->setOption('query', $primary_button['query']);
      }

      $element['primary'] = [
        '#type' => 'link',
        '#title' => $primary_button['title'],
        '#url' => $url,
        '#button_type' => !empty($element['#primary_button_type']) ? $element['#primary_button_type'] : 'outline-outline-gray',
        '#button_size' => !empty($element['#primary_button_size']) ? $element['#primary_button_size'] : 'sm',
      ];
    }
    else {
      $element['primary'] = $primary_button['title'];
    }

    $element['toggle'] = [
      '#type' => 'button',
      '#value' => t('Toggle Dropdown'),
      '#button_type' => isset($element['#toggle_type']) ? $element['#toggle_type'] : 'light',
      '#button_size' => 'sm',
      '#attributes' => [
        'data-toggle' => 'dropdown',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
        'class' => [
          'dropdown-toggle',
          'dropdown-toggle-split',
          'p-0',
          'px-2',
        ],
      ],
    ];

    if (!empty($element['#attributes_toggle'])) {
      $element['toggle']['#attributes'] = NestedArray::mergeDeep($element['toggle']['#attributes'], $element['#attributes_toggle']);
    }

    if (!empty($element['#tooltip'])) {
      $element['toggle']['#attributes']['title'] = $element['#tooltip'];
      $element['toggle']['#attributes']['data-tooltip'] = 'left';
    }

    if (!empty($element['#hotkey'])) {
      $element['toggle']['#attributes']['data-hotkey'] = $element['#hotkey'];
    }

    $element['#attributes_dropdown']['class'][] = 'dropdown-menu';
    if (!isset($element['#attributes_dropdown'])) {
      $element['#attributes_dropdown']['class'][] = 'shadow-sm';
    }

    $element['dropdown'] = [
      '#type' => 'container',
      '#attributes' => $element['#attributes_dropdown'],
    ];

    foreach ($element['#links'] as $id => &$link) {

      if (!empty($url)) {
        /** @var \Drupal\Core\Url $url */
        $url = $link['url'];
        if (!empty($link['query'])) {
          $url->setOption('query', $link['query']);
        }

        $element['dropdown'][$id] = [
          '#type' => 'link',
          '#title' => $link['title'],
          '#url' => $url,
          '#attributes' => [
            'type' => 'button',
            'class' => isset($element['#attributes_button']) ? $element['#attributes_button'] : [
              'dropdown-item',
              'text-left',
              'py-2',
              'px-3',
              'small',
              'border-bottom',
            ],
          ],
        ];
      }
      else {
        $element['dropdown'][$id] = $link['title'];
      }
    }

    return $element;
  }

  /**
   * Process callback for details element.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return mixed
   */
  public static function processDetails(&$element, FormStateInterface $form_state, &$complete_form) {
    return $element;
    if (empty($element['#open']) && !empty($element['#ajax_toggle'])) {
      // static::recurseSetAccess($element);
      $element['#ajax'] = [
        'callback' => [static::class, 'ajaxOpDetails'],
        'event' => 'click',
        'wrapper' => $complete_form['#ajax_wrapper'],
      ];

      $element['mock_button'] = [
        '#type' => 'button',
        '#value' => t('Open'),
      ];

      $element['mock_button']['#ajax'] = [
        'callback' => [static::class, 'ajaxOpDetails'],
      ];

    }

    return $element;
  }

  /**
   * Ajax callback to show or hide details children.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function ajaxOpDetails(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * @param array $element
   * @param bool $access
   */
  public static function recurseSetAccess(array &$element, $access = FALSE) {

    foreach ($element as &$child) {

      if (is_array($child)) {

        if (!empty($child['#type'])) {
          $child['#access'] = $access;
        }

        // static::recurseSetAccess($child);
      }

    }

  }

  /**
   * @param array $element
   *
   * @return array
   */
  public static function processTextFormat(&$element, FormStateInterface $form_state, &$complete_form) {

    // Conditionally hide format select field.
    if (!empty($element['#hide_format'])) {
      $element['format']['#access'] = FALSE;
    }

    // Always hide guidelines on text_format element.
    if (!empty($element['format']['guidelines'])) {
      $element['format']['guidelines']['#access'] = FALSE;
    }

    // Always hide help link on text_format element.
    if (!empty($element['format']['help'])) {
      $element['format']['help']['#access'] = FALSE;
    }

    return $element;
  }

  /**
   * Pre render callback for toolbar element.
   *
   * @param array $element
   *   The toolbar element.
   *
   * @return array
   *   The processed toolbar element.
   */
  public static function preRenderToolbar(array $element) {
    static::recurseSetCache($element);
    return $element;
  }

  /**
   * Pre render callback for view.
   *
   * @param array $element
   *   The toolbar element.
   *
   * @return array
   *   The processed toolbar element.
   */
  public static function preRenderView(array $element) {
    return $element;
  }

  /**
   * @param array $element
   *
   * @return array
   */
  public static function preRenderConfigAjax(array $element) {
    return $element;
  }

  /**
   * @param array $element
   *
   * @return array
   */
  public static function preRenderConfigButton(array $element) {

    $static_cache = &drupal_static(__FUNCTION__, []);
    if (empty($static_cache['icon_map'])) {
      $static_cache['icon_map'] = \Drupal::service('design.system')->getOption('icon.button_label');
    }

    // @todo temporarily disable auto icons for buttons.
    $static_cache['icon_map'] = [];

    if (empty($element['#attributes'])) {
      $element['#attributes'] = [];
    }

    // Support #title property on button and submit elements.
    if (!empty($element['#title'])) {
      if (is_array($element['#title'])) {
        // @todo workaround because of array to string conversion.
        $element['#title'] = \Drupal::service('renderer')->render($element['#title']);
      }
      $element['#value'] = $element['#title'];
    }
    elseif (!empty($element['#value']) && empty($element['#title'])) {
      $element['#title'] = $element['#value'];
    }

    if (!empty($element['#title'])) {
      // Element can be either link, button, or submit with different label
      // property.
      $title = &$element['#title'];

      $title_compare = $title;

      if (is_object($title_compare)) {
        $title_compare = $title_compare->__toString();
      }
      elseif (is_array($title_compare)) {
        if (!empty($title_compare['#value'])) {
          $title_compare = $title_compare['#value'];
        }
        elseif (!empty($title_compare['label']['#value'])) {
          $title_compare = $title_compare['label']['#value'];
        }
        elseif (!empty($title_compare['#context']['value'])) {
          $title_compare = $title_compare['#context']['value'];
        }
        else {
          $title_compare = NULL;
        }
      }

      if (!empty($element['#title']) && in_array($element['#type'], [
        'button',
        'submit',
      ])) {
        if (!empty($static_cache['icon_map'])) {
          foreach ($static_cache['icon_map'] as $string_pattern => $icon_id) {
            if (($title_compare == $string_pattern) || ("{$title_compare}*" == $string_pattern) || fnmatch($string_pattern, $title_compare)) {
              $element['#icon'] = $icon_id;
              break;
            }
          }
        }
      }
    }

    if (!empty($element['#modal_size'])) {
      $element['#attributes']['class'][] = 'use-ajax';
      $element['#attributes']['data-dialog-type'] = 'modal';
      $element['#attributes']['data-dialog-size'] = $element['#modal_size'];

      if (!empty($element['#attributes']['data-dialog-renderer'])) {
        unset($element['#attributes']['data-dialog-renderer']);
      }
    }

    if (!empty($element['#button_type'])) {
      if (empty($element['#attributes']['class']) || !in_array('btn', $element['#attributes']['class'])) {
        $element['#attributes']['class'][] = 'btn';
      }
      $element['#attributes']['class'][] = "btn-{$element['#button_type']}";
    }

    if (!empty($element['#button_size'])) {
      $element['#attributes']['class'][] = "btn-{$element['#button_size']}";
    }

    // Submit buttons can't have markup or icons.
    if (!empty($element['#options']['attributes']['data-icon'])) {
      $icon = $element['#options']['attributes']['data-icon'];
    }
    elseif (!empty($element['#attributes']['data-icon'])) {
      $icon = $element['#attributes']['data-icon'];
    }
    elseif (!empty($element['#icon'])) {
      $icon = $element['#icon'];
    }

    $icon_size = "";
    if (!empty($element['#icon_size'])) {
      $icon_size = " {$element['#icon_size']}";
    }
    elseif (!empty($element['#options']['attributes']['data-icon-size'])) {
      $icon_size = " {$element['#options']['attributes']['data-icon-size']}";
    }

    if (!empty($title) && !empty($icon)) {

      if (!empty($element['#options']['attributes']['data-icon-color-bg'])) {
        $icon_markup = "<i class=\"fa fa-{$icon}{$icon_size} bg-{$element['#options']['attributes']['data-icon-color-bg']}\"></i>";
      }
      else {
        $icon_markup = "<i class=\"fa fa-{$icon}{$icon_size}\"></i>";
      }

      if (!empty($element['#options']['attributes']['data-icon-position'])) {
        $icon_position = $element['#options']['attributes']['data-icon-position'];
      }
      elseif (!empty($element['#icon_position'])) {
        $icon_position = $element['#icon_position'];
      }
      else {
        $icon_position = 'before';
      }

      if ($icon_position == 'below') {
        $icon_markup = "<span class=\"icon--outside icon--below\">{$icon_markup}</span>";
      }
      elseif ($icon_position == 'above') {
        $icon_markup = "<span class=\"icon--outside icon--above\">{$icon_markup}</span>";
      }
      elseif ($icon_position == 'after') {
        $icon_markup = "<span class=\"icon--after\">{$icon_markup}</span>";
      }
      elseif ($icon_position == 'only') {
        $icon_markup = "<span class=\"icon--only\">{$icon_markup}</span>";
      }
      else {
        $icon_markup = "<span class=\"icon--before\">{$icon_markup}</span>";
      }

      $element['#attributes']['data-icon-position'] = $icon_position;

      if (is_object($title)) {
        $title = $title->__toString();
      }
      elseif (is_array($title)) {
        // @todo lazy builder in some cases.
        return $element;
      }
      $title = "{$icon_markup} <span class=\"item--label\">{$title}</span>";
      $title = [
        '#markup' => $title,
      ];

      // Value needs to be plain text value as its an attribute on submit
      // element.
      $element['#value'] = $title;
      $element['#attributes']['value'] = $title_compare;
      $element['#title'] = $title;

    }

    return $element;
  }

  /**
   * @param array $element
   *
   * @return array
   */
  public static function preRenderPrefixSuffix(array $element) {

    if (isset($element['#prefix']) && is_array($element['#prefix'])) {
      $element['#prefix'] = \Drupal::service('renderer')->render($element['#prefix']);
    }

    if (isset($element['#suffix']) && is_array($element['#suffix'])) {
      $element['#suffix'] = \Drupal::service('renderer')->render($element['#suffix']);
    }

    if (is_array($element['#value'])) {
      $element['#value'] = \Drupal::service('renderer')->render($element['#value']);
    }

    if (!empty($element['#tag'])) {
      if (in_array($element['#tag'], ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
        $element['#attributes']['class'][] = 'text--heading';
      }
      elseif (in_array($element['#tag'], ['button', 'submit'])) {
        if (!in_array('btn', $element['#attributes']['class'])) {
          $element['#attributes']['class'][] = 'btn';
          $element['#attributes']['class'][] = 'btn-sm';
          $element['#attributes']['class'][] = 'btn-outline-primary';
        }
      }
    }

    return $element;
  }

  /**
   * Attach modal attirbutes to a given element.
   *
   * @param array $attributes
   *   The attributes.
   * @param string $modal_name
   *   The modal name.
   */
  public static function attachModalAttributes(&$attributes = [], $modal_name) {
    $attributes['data-dialog-type'] = 'dialog';
    $attributes['data-dialog-size'] = 'fw';
    $attributes['data-ajax-throbber'] = 'slider';
    $attributes['data-dialog-target'] = $modal_name;
    $attributes['data-dialog-class'] = $modal_name;
    $attributes['class'][] = 'contextual-link--item';

    if (empty($attributes['class']) || !in_array('use-ajax', $attributes['class'])) {
      $attributes['class'][] = 'use-ajax';
    }

    if (!empty($attributes['data-dialog-renderer'])) {
      unset($attributes['data-dialog-renderer']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function recurseSetCache(array &$build, $contexts = ['user.permissions', 'languages'], $max_age = -1, $cache_tags = []) {
    foreach ($build as $key => &$child) {
      if (is_array($child)) {
        if ($key == '#cache' && isset($child['contexts'])) {
          $child['contexts'] = $contexts;
          $child['max-age'] = $max_age;
          if (!empty($cache_tags)) {
            $child['tags'] = $cache_tags;
          }
        }
        static::recurseSetCache($child, $contexts, $max_age, $cache_tags);
      }
    }
  }

  /**
   * @param array $build
   */
  public static function convertStyleArrayToString(array &$build) {

    // @todo this is called from both Normalizer and Preprocess, sometimes twice
    // for same style data.
    if (isset($build['#attributes'])) {
      $attributes = &$build['#attributes'];
    }
    elseif (isset($build['attributes'])) {
      $attributes = &$build['attributes'];
    }
    else {
      return;
    }

    if (!empty($attributes['style']) && is_array($attributes['style'])) {
      $style = "";
      foreach ($attributes['style'] as $key => $value) {
        $style .= "{$key}: {$value};";
      }
      $attributes['style'] = $style;
    }
  }

}
