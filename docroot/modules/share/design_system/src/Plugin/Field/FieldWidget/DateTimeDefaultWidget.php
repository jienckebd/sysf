<?php

namespace Drupal\design_system\Plugin\Field\FieldWidget;

use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget as Base;

/**
 * Extends date time default widget.
 */
class DateTimeDefaultWidget extends Base {

  use DateTimeWidgetTrait;

}
