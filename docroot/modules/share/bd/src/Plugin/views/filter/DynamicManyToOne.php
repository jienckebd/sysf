<?php

namespace Drupal\bd\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Builds value options based on result set values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("dynamic_many_to_one")
 */
class DynamicManyToOne extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [];

    $view = $this->view;
    if (empty($view->executing)) {

      $filter_key = $this->getFilterKey();
      $view_id = $view->id();
      $display_id = $view->current_display;
      $cid = "dmto__{$view_id}__{$display_id}__{$filter_key}";
      $cache = \Drupal::cache();

      if ($options_data = $cache->get($cid)) {
        $options_unprocessed = $options_data->data;
      }
      else {

        $view->executing = TRUE;

        $this->cacheReset();
        $view->execute();
        $this->cacheReset();

        $view->built = FALSE;
        $view->executed = FALSE;
        $cache_plugin = $view->display_handler->getPlugin('cache');
        $cache_plugin->cacheFlush();

        $options_unprocessed = [];
        $style_plugin = $this->view->style_plugin;
        foreach ($this->view->result as $row_index => $row) {

          $value_raw = $style_plugin->getFieldValue($row_index, 'field_date_range');
          $value = $style_plugin->getField($row_index, 'field_date_range');

          $value = strip_tags($value);
          $value = str_replace("\n", "", $value);

          $options_unprocessed[$value_raw] = $value;
        }

        $options_unprocessed = array_unique($options_unprocessed);
        if (!empty($options_unprocessed)) {
          $cache->set($cid, $options_unprocessed);
        }
      }

      foreach ($options_unprocessed as $key => $value) {
        $this->valueOptions[$key] = $value;
      }

      $exposed_input = $view->getExposedInput();
      if (empty($exposed_input[$filter_key])) {
        if ($first_option = key($this->valueOptions)) {
          $this->options['value']['default'] = $first_option;
          $view->exposed_data[$filter_key] = $first_option;
          $view->exposed_raw_input[$filter_key] = $first_option;
          $exposed_input[$filter_key] = $first_option;
          $view->setExposedInput($exposed_input);
          $this->value = [
            'value' => $first_option,
            'type' => 'date',
          ];
          $this->options['value']['value'] = $first_option;
          $this->options['value']['type'] = 'date';
        }
      }

      $this->always_required = TRUE;

    }

    return $this->valueOptions;

  }

  /**
   * Reset caches on view.
   */
  public function cacheReset() {
    $view = $this->view;
    $view->built = FALSE;
    $view->executed = FALSE;
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $cache_plugin->cacheFlush();
  }

  /**
   * @return bool|null
   */
  public function getFilterKey() {
    $filter_form_key = NULL;

    if (!empty($this->options['expose']['identifier'])) {
      $filter_form_key = $this->options['expose']['identifier'];
    }
    elseif (!empty($this->options['id'])) {
      $filter_form_key = $this->options['id'];
    }
    else {
      // @todo log warning.
      return FALSE;
    }

    return $filter_form_key;
  }

}
