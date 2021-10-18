<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget as Base;

/**
 * Extends date time range widget.
 */
class DateRangeDefaultWidget extends Base {

  use DateTimeWidgetTrait;

}
