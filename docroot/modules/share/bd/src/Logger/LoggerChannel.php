<?php

namespace Drupal\bd\Logger;

use Drupal\Core\Logger\LoggerChannel as Base;
use Drupal\Core\Entity\EntityInterface;

/**
 * Extends core logger channel.
 */
class LoggerChannel extends Base implements LoggerChannelInterface {

  /**
   * {@inheritdoc}
   */
  public function logEntity(EntityInterface $entity, $level, $message, array $context = []) {
    $this->log($level, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function logEntityField(EntityInterface $entity, $field_name, $level, $message, array $context = []) {
    $this->log($level, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function logException($e, $level, $message, array $context = []) {
    parent::log($level, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {

    if (stripos($message, '@place') !== FALSE) {

      $place = "";
      $backtrace = debug_backtrace();

      if (!empty($backtrace[1])) {
        $caller = $backtrace[1];
        $place = "file {$caller['file']} / line {$caller['line']}";
      }

      $context['@place'] = $place;
    }

    parent::log($level, $message, $context);
  }

}
