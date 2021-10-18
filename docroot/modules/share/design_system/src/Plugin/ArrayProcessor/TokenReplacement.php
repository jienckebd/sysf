<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;

/**
 * Replace token values.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "token_replacement",
 *   label = @Translation("Token replacement"),
 *   description = @Translation("Replaces tokens with their values."),
 * )
 */
class TokenReplacement extends Base {

  /**
   * Array keys to replace.
   *
   * @var array
   */
  const CONFIG = [
    'key_replace' => [
      '#title',
      '#value',
      '#markup',
      '#text',
    ],
  ];

  use AjaxHelperTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {

    $token_context = [];

    $this->recurseProcessBuild($build, $token_context);

  }

  /**
   * @param array $build
   * @param array $token_context
   */
  protected function recurseProcessBuild(array &$build, array &$token_context = []) {

    foreach (static::CONFIG['key_replace'] as $key_replace) {
      if (isset($build[$key_replace])) {
        if (is_string($build[$key_replace])) {
          $build[$key_replace] = \Drupal::token()->replace($build[$key_replace], $token_context);
        }
      }
    }

    foreach (Element::children($build) as $child_key) {

      $child = &$build[$child_key];
      if (!is_array($child)) {
        continue;
      }

      $this->recurseProcessBuild($child, $token_context);

    }

  }

}
