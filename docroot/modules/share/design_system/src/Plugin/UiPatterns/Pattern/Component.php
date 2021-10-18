<?php

namespace Drupal\design_system\Plugin\UiPatterns\Pattern;

use Drupal\ui_patterns\Plugin\PatternBase;

/**
 * The UI Pattern plugin.
 *
 * @UiPattern(
 *   id = "component",
 *   label = @Translation("Component Pattern"),
 *   description = @Translation("Pattern provided by a Pattern Lab instance."),
 *   deriver = "\Drupal\design_system\Plugin\Derivative\UiPatternComponentType"
 * )
 */
class Component extends PatternBase {

}
