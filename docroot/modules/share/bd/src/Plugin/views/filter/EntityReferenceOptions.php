<?php

namespace Drupal\bd\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_reference_options")
 */
class EntityReferenceOptions extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {

    $feeds = \Drupal::service('entity.helper')->getStorage('feeds_feed')->loadMultiple();

    $options = [];

    foreach ($feeds as $key => $feed) {
      $options[$feed->id()] = $feed->label();
    }

    $this->valueOptions = $options;

    return $this->valueOptions;
  }

}
