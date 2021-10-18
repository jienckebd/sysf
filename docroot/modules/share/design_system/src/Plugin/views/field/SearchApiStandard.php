<?php

namespace Drupal\design_system\Plugin\views\field;

use Drupal\search_api\Plugin\views\field\SearchApiStandard as Base;
use Drupal\Core\Render\Markup;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;

/**
 * Extends search_api standard views field handler.
 */
class SearchApiStandard extends Base implements MultiItemsFieldHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    if ($this->field_alias == 'rendered_item') {
      $item['value'] = Markup::create($item['value']);
    }
    return parent::render_item($count, $item);
  }

}
