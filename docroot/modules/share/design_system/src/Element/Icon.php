<?php

namespace Drupal\design_system\Element;

use Drupal\Core\Render\Element\HtmlTag;

/**
 * Provides an icon render element.
 *
 * @RenderElement("icon")
 */
class Icon extends HtmlTag {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    $info['#pre_render'] = [
      [$class, 'preRenderIcon'],
      [$class, 'preRenderConditionalComments'],
      [$class, 'preRenderHtmlTag'],
    ];
    return $info;

  }

  /**
   * Pre-render callback: Renders an icon with font awesome.
   *
   * @param array $element
   *
   * @return array
   */
  public static function preRenderIcon($element) {

    $element['#type'] = 'html_tag';
    $element['#tag'] = 'i';
    $element['#value'] = '';
    $element['#attributes']['class'][] = 'icon';
    $element['#attributes']['class'][] = 'fa';
    $element['#attributes']['class'][] = "fa-{$element['#icon']}";

    if (!empty($element['#size'])) {
      $element['#attributes']['class'][] = "{$element['#size']}";
    }

    return $element;
  }

}
